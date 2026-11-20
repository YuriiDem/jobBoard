<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Job;
use App\Services\Seo;
use App\Services\TaxonomyResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BoardController extends Controller
{
    public function __construct(private TaxonomyResolver $taxonomy) {}

    public function home(): View|RedirectResponse
    {
        if (request()->has('page')) {
            return $this->singlePageRedirect();
        }

        return $this->view('home', config('jobboard.site_name'));
    }

    private function view(string $name, string $heading, array $data = [], bool $eligible = true): View
    {
        return view($name, $data + ['heading' => $heading, 'meta' => Seo::meta($heading, '', $eligible)]);
    }

    public function feed(): View|RedirectResponse
    {
        return $this->listing(Job::active(), config('jobboard.niche_name'));
    }

    public function single(string $slug): View|RedirectResponse
    {
        $term = $this->taxonomy->find($slug);
        abort_unless($term, 404);
        if ($term->kind === 'jobs') {
            $job = Job::with(['company', 'primaryLocation', 'locations', 'department', 'role'])->findOrFail($term->owner_id);
            abort_if($job->status === 'expired' || ($job->status === 'published' && $job->expires_at?->isPast()), 410);
            abort_unless($job->status === 'published' && $job->published_at && ! $job->published_at->isFuture(), 404);
            if (request()->has('page')) {
                return $this->singlePageRedirect();
            }
            $job->track('views_count');
            $topics = collect([$job->department, $job->role])->filter()->pluck('name', 'slug');
            $employment = array_search($job->employment_type, config('jobboard.employment'), true);
            if ($employment !== false) {
                $topics[$employment] = str($employment)->headline();
            }
            foreach (array_merge(array_intersect($job->tags ?? [], config('jobboard.featured_tags')), array_intersect($job->company->markets ?? [], config('jobboard.markets'))) as $name) {
                $topics[Str::slug($name)] = $name;
            }
            $places = $job->locations->concat([$job->primaryLocation])->filter()->pluck('name', 'slug');
            if ($job->workplace_type === 'remote') {
                $places['remote'] = 'Remote';
            }
            $hubs = $topics->union($places);
            foreach (config('jobboard.approved_pairs') as $pair) {
                [$topic,$place] = array_pad(explode('/', $pair), 2, null);
                if ($topics->has($topic) && $places->has($place)) {
                    $hubs[$pair] = $topics[$topic].' in '.$places[$place];
                }
            }
            $schema = null;
            $description = $job->description_plain ?: strip_tags($job->description_html ?? '');
            if ($description && $job->primaryLocation?->country_code && $job->workplace_type !== 'remote') {
                $schema = ['@context' => 'https://schema.org', '@type' => 'JobPosting', 'title' => $job->title, 'description' => $description, 'datePosted' => $job->published_at->toAtomString(), 'hiringOrganization' => ['@type' => 'Organization', 'name' => $job->company->name], 'jobLocation' => ['@type' => 'Place', 'address' => ['@type' => 'PostalAddress', 'addressCountry' => $job->primaryLocation->country_code]]];
                if ($job->expires_at) {
                    $schema['validThrough'] = $job->expires_at->toAtomString();
                }
            }

            return $this->view('job', $job->title, compact('job', 'hubs', 'schema'));
        }
        $query = Job::active();
        $this->taxonomy->constrain($query, $term);

        return $this->listing($query, str($slug)->headline()->toString());
    }

    public function pair(string $topic, string $location): View|RedirectResponse
    {
        $first = $this->taxonomy->find($topic);
        $second = $this->taxonomy->find($location);
        abort_unless($first && $second && $first->kind !== 'jobs' && $second->kind !== 'jobs' && $this->taxonomy->isLocation($first) !== $this->taxonomy->isLocation($second), 404);
        if ($this->taxonomy->isLocation($first)) {
            return redirect()->to(Seo::url("jobs/$location/$topic"), 301);
        }
        $query = Job::active();
        $this->taxonomy->constrain($query, $first);
        $this->taxonomy->constrain($query, $second);
        $eligible = in_array("$topic/$location", config('jobboard.approved_pairs'), true) && $this->taxonomy->eligible($query);

        return $this->listing($query, str("$topic in $location")->headline()->toString(), $eligible);
    }

    public function locationAlias(string $slug): RedirectResponse
    {
        $term = $this->taxonomy->find($slug);
        abort_unless($term && $this->taxonomy->isLocation($term), 404);

        return redirect()->to(Seo::url("jobs/$slug"), 301);
    }

    private function listing(Builder $query, string $heading, bool $eligible = true, ?Company $company = null): View|RedirectResponse
    {
        $filters = [];
        foreach (request()->query() as $key => $value) {
            abort_unless(is_string($value) && strlen($value) <= 255 && in_array($key, [...config('jobboard.filters'), 'page'], true), 404);
            if ($key !== 'page' && trim($value) !== '') {
                $filters[$key] = trim($value);
            }
        }
        ksort($filters);
        foreach ($filters as $key => $value) {
            match ($key) {
                'stage' => $query->whereHas('company', fn ($q) => $q->where('funding_stage', $value)),
                'min_salary' => ctype_digit($value) && strlen($value) <= 9 ? $query->where('salary_currency', config('jobboard.default_currency'))->where('salary_period', 'year')->where('salary_min', '>=', (int) $value) : abort(404),
                'workplace' => $query->where('workplace_type', $value),
                'tag' => $query->whereJsonContains('tags', $value),
                'q' => $query->whereRaw("search_vector @@ websearch_to_tsquery('simple', ?)", [$value]),
            };
        }
        $page = $this->page();
        $params = $filters;
        if ($page > 1) {
            $params['page'] = $page;
        }
        $canonicalQuery = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        if ((string) request()->server('QUERY_STRING') !== $canonicalQuery) {
            return redirect()->to(Seo::url(request()->path()).($canonicalQuery ? '?'.$canonicalQuery : ''), 301);
        }
        $jobs = $query->listing()->orderByDesc('is_featured')->orderByDesc('published_at')->orderByDesc('id')->simplePaginate(config('jobboard.jobs_per_page'))->withPath(Seo::url(request()->path()))->appends($filters);
        abort_if($page > 1 && $jobs->isEmpty(), 404);

        return $this->view('listing', $heading, compact('jobs', 'filters', 'company'), $eligible && $jobs->isNotEmpty());
    }

    private function singlePageRedirect(): RedirectResponse
    {
        abort_unless($this->page() === 1, 404);

        return redirect()->to(Seo::url(request()->path()), 301);
    }

    private function page(): int
    {
        $page = request()->query('page', '1');
        abort_unless(is_string($page) && preg_match('/^[1-9][0-9]{0,8}$/D', $page), 404);

        return (int) $page;
    }

    public function apply(string $slug): RedirectResponse
    {
        $job = Job::active()->where('slug', $slug)->firstOrFail();
        $job->track('apply_clicks_count');

        return redirect()->away($job->apply_url)->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function company(Company $company): View|RedirectResponse
    {
        return $this->listing(Job::active()->where('company_id', $company->id), $company->name, true, $company);
    }

    public function companies(): View|RedirectResponse
    {
        $page = $this->page();
        abort_if(array_diff(array_keys(request()->query()), ['page']), 404);
        if ($page === 1 && request()->has('page')) {
            return redirect()->to(Seo::url('companies'), 301);
        }
        $companies = Company::whereHas('jobs', fn ($query) => $query->active())->orderBy('name')->orderBy('id')->simplePaginate(config('jobboard.jobs_per_page'), ['name', 'slug'])->withPath(Seo::url('companies'));
        abort_if($page > 1 && $companies->isEmpty(), 404);

        return $this->view('companies', 'Hiring companies', compact('companies'));
    }

    public function salary(): View|RedirectResponse
    {
        if (request()->has('page')) {
            return $this->singlePageRedirect();
        }
        $currency = config('jobboard.default_currency');
        $median = Cache::remember("salary.guide.$currency", now()->addHours(6), fn () => Job::active()->where('salary_currency', $currency)->where('salary_period', 'year')->whereNotNull('salary_min')->toBase()->selectRaw('percentile_cont(0.5) within group (order by salary_min) as median')->first()->median);

        return $this->view('salary', 'Salary guide', compact('median', 'currency'));
    }
}
