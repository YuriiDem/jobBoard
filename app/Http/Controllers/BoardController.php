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

        return view('home', \App\Services\Homepage::data(\App\Models\HomepageContent::published()));
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
            $kinds = \Illuminate\Support\Facades\DB::table('public_slugs')->whereIn('slug', $hubs->keys())
                ->pluck('kind', 'slug');
            $hubs = $hubs->mapWithKeys(fn ($name, $slug) => [TaxonomyResolver::path($kinds[$slug] ?? '', $slug) => ['name' => $name, 'kind' => $kinds[$slug] ?? 'pair']]);
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
        $path = TaxonomyResolver::path($term->kind, $term->slug);
        if ($path !== 'jobs/'.$slug) {
            return $this->redirectHub($path);
        }
        $query = Job::active();
        $this->taxonomy->constrain($query, $term);

        $name = Str::headline($slug);
        $response = $this->listing($query, $name);
        if ($response instanceof View) {
            $response->with(['termKind' => $term->kind, 'termName' => $name]);
        }
        return $response;
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

    private function redirectHub(string $path): RedirectResponse
    {
        $query = request()->getQueryString();

        return redirect()->to(Seo::url($path).($query ? '?'.$query : ''), 301);
    }

    public function topicPage(string $slug): View|RedirectResponse
    {
        $term = $this->taxonomy->find($slug);
        abort_unless($term && in_array($term->kind, ['tags', 'remote'], true), 404);

        return $this->landing($term, $term->kind === 'remote' ? 'Remote' : $term->value);
    }

    public function termPage(string $directory, string $slug): View|RedirectResponse
    {
        $term = $this->taxonomy->find($slug);
        if ($directory === 'locations' && $term?->kind === 'remote') {
            return $this->redirectHub('remote-jobs');
        }
        abort_unless($term && $term->kind === TaxonomyResolver::DIRECTORIES[$directory], 404);
        $name = $this->taxonomy->terms($term->kind)->where('s.slug', $slug)->firstOrFail()->name;

        return $this->landing($term, $name);
    }

    private function landing(object $term, string $name): View|RedirectResponse
    {
        $query = Job::active();
        $this->taxonomy->constrain($query, $term);
        $heading = $term->kind === 'locations' ? 'Jobs in '.$name : $name.' jobs';
        $intro = match ($term->kind) {
            'locations' => "Browse current vacancies with a listed work location in $name.",
            'roles' => "Explore current openings for the $name role.",
            'markets' => "Explore employers hiring in the $name market and their current openings.",
            'tags' => "Browse vacancies that list $name among their technologies or skills.",
            default => 'Browse current openings that employers have marked as remote.',
        };
        $response = $this->listing($query, $heading);
        if ($response instanceof View) {
            $meta = $response->getData()['meta'];
            $meta['title'] = $heading.($this->page() > 1 ? ' - Page '.$this->page() : '').' | '.config('jobboard.site_name');
            $meta['description'] = $intro;
            $response->with(['meta' => $meta, 'intro' => $intro, 'termKind' => $term->kind, 'termName' => $name]);
        }
        return $response;
    }

    public function termDirectory(string $directory): View|RedirectResponse
    {
        $page = $this->page();
        abort_if(array_diff(array_keys(request()->query()), ['page']), 404);
        if ($page === 1 && request()->has('page')) {
            return redirect()->to(Seo::url($directory), 301);
        }
        $terms = $this->taxonomy->terms(TaxonomyResolver::DIRECTORIES[$directory])
            ->orderBy('name')->orderBy('s.slug')->simplePaginate(config('jobboard.jobs_per_page'))->withPath(Seo::url($directory));
        abort_if($page > 1 && $terms->isEmpty(), 404);
        $heading = 'Browse jobs by '.['roles'=>'role', 'locations'=>'location', 'collections'=>'collection', 'tags'=>'technology', 'departments'=>'department', 'employment-types'=>'employment type'][$directory];
        $meta = Seo::meta($heading, '', $terms->isNotEmpty());
        $meta['title'] = $heading.($page > 1 ? ' - Page '.$page : '').' | '.config('jobboard.site_name');
        $meta['description'] = $heading.' and explore the matching vacancies on '.config('jobboard.site_name').'.';

        return view('term-directory', compact('heading', 'terms', 'meta'));
    }

    private function listing(Builder $query, string $heading, bool $eligible = true, ?Company $company = null): View|RedirectResponse
    {
        $filters = [];
        $unfiltered = clone $query;
        foreach (request()->query() as $key => $value) {
            $value ??= '';
            if ($key === 'tag' && is_array($value)) {
                abort_if(count($value) > 16, 404);
                foreach ($value as $tag) {
                    abort_unless(is_string($tag) && strlen($tag) <= 120, 404);
                }
                $tags = array_values(array_unique(array_filter(array_map('trim', $value), 'strlen')));
                sort($tags, SORT_STRING);
                if ($tags) {
                    $filters['tag'] = count($tags) === 1 ? $tags[0] : $tags;
                }
                continue;
            }
            if (in_array($key, ['role', 'location', 'level', 'employment', 'workplace', 'stage'], true)) {
                $terms = is_array($value) ? $value : explode(',', $value ?? '');
                abort_if(count($terms) > 16, 404);
                foreach ($terms as $term) {
                    abort_unless(is_string($term) && strlen($term) <= 120, 404);
                }
                $terms = array_unique(array_filter(array_map(fn ($term) => trim($term), $terms)));
                sort($terms, SORT_STRING);
                $value = implode(',', $terms);
            }
            abort_unless(is_string($value) && strlen($value) <= 255 && in_array($key, [...config('jobboard.filters'), 'page'], true), 404);
            if ($key !== 'page' && trim($value) !== '') {
                $filters[$key] = trim($value);
            }
        }
        ksort($filters);
        foreach ($filters as $key => $value) {
            match ($key) {
                'stage' => $query->whereHas('company', fn ($q) => $q->whereIn('funding_stage', explode(',', $value))),
                'min_salary' => ctype_digit($value) && strlen($value) <= 9 ? $query->where('salary_currency', config('jobboard.default_currency'))->where('salary_period', 'year')->where('salary_min', '>=', (int) $value) : abort(404),
                'workplace' => $query->whereIn('workplace_type', explode(',', $value)),
                'employment' => $query->whereIn('employment_type', explode(',', $value)),
                'level' => $query->whereIn('seniority_level', explode(',', $value)),
                'role' => $query->whereHas('role', fn ($q) => $q->whereIn('slug', array_map([Str::class, 'slug'], explode(',', $value)))),
                'location' => $query->where(function ($q) use ($value) {
                    $places = array_map([Str::class, 'slug'], explode(',', $value));
                    $matches = fn ($place) => $place->whereIn('slug', $places);
                    $q->whereHas('primaryLocation', $matches)->orWhereHas('locations', $matches);
                }),
                'tag' => $query->where(function ($q) use ($value) {
                    foreach ((array) $value as $tag) {
                        $q->orWhereJsonContains('tags', $tag);
                    }
                }),
                'q' => $query->where(function ($search) use ($value) {
                    $search->whereRaw("search_vector @@ websearch_to_tsquery('simple', ?)", [$value])
                        ->orWhereRaw('(lower(tags::text)::jsonb) @> jsonb_build_array(lower(?::text))', [$value]);
                }),
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

        $options = \App\Services\JobFilterOptions::forListing($unfiltered, request()->path());

        return $this->view('listing', $heading, compact('jobs', 'filters', 'company', 'options'), $eligible && $jobs->isNotEmpty());
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
        $companies = Company::whereHas('jobs', fn ($query) => $query->active())->orderBy('name')->orderBy('id')->simplePaginate(config('jobboard.jobs_per_page'), ['name', 'slug', 'logo_url'])->withPath(Seo::url('companies'));
        abort_if($page > 1 && $companies->isEmpty(), 404);

        return $this->view('companies', 'Hiring companies', compact('companies'));
    }

    public function salary(): View|RedirectResponse
    {
        if (request()->has('page')) {
            return $this->singlePageRedirect();
        }
        $currency = config('jobboard.default_currency');
        $band = Cache::remember("salary.band.$currency", now()->addHours(6), fn () => Job::active()
            ->where('salary_currency', $currency)->where('salary_period', 'year')->whereNotNull('salary_min')
            ->toBase()->selectRaw('percentile_cont(0.25) within group (order by salary_min) as p25,
                percentile_cont(0.5) within group (order by salary_min) as median,
                percentile_cont(0.75) within group (order by salary_min) as p75')->first());

        return $this->view('salary', 'Salary guide', compact('band', 'currency'));
    }
}
