<?php

namespace Tests\Feature;

use App\Models\Job;
use App\Services\TaxonomyResolver;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PublicDirectoryTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        config(['jobboard.featured_tags' => ['Ocean'], 'jobboard.approved_pairs' => ['internships/remote'], 'jobboard.jobs_per_page' => 2]);
        app(TaxonomyResolver::class)->sync();
        $this->seed(DatabaseSeeder::class);
        Job::query()->update(['status' => 'published', 'published_at' => now()->subDay(), 'salary_min' => 50000, 'salary_period' => 'year']);
    }

    public function test_directory_pagination_filters_and_hub_discovery(): void
    {
        DB::enableQueryLog();
        $this->get('/jobs')->assertOk()->assertSeeInOrder(['Startup Specialist', 'Startup Student Associate'])->assertDontSee('Startup Intern');
        $this->assertLessThanOrEqual(3, count(DB::getQueryLog()));
        $this->get('/jobs?page=2')->assertOk()->assertSee('Startup Intern')->assertSee('href="http://localhost/jobs?page=2"', false);
        $this->get('/jobs?page=1')->assertRedirect('/jobs');
        $this->get('/jobs?page=3')->assertNotFound();
        $this->get('/jobs?page[]=1')->assertNotFound();
        $this->get('/jobs?q=Intern&tag=Ocean')->assertOk()->assertSee('Startup Intern')->assertDontSee('Startup Specialist')->assertSee('noindex, follow');
        $this->get('/jobs/remote/internships')->assertRedirect('/jobs/internships/remote')->assertStatus(301);
        $this->get('/jobs/internships/remote')->assertOk()->assertSee('content="index, follow"', false);
        $this->get('/jobs/contracts')->assertOk()->assertSee('noindex, follow');
        $this->get('/jobs/ocean/remote')->assertOk()->assertSee('noindex, follow');
        $this->get('/jobs/internships/contracts')->assertNotFound();
        $this->get('/jobs/demo-internship-opening')->assertOk()->assertSee('jobs/internships/remote');
    }

    public function test_multiselect_filters_share_a_normalized_url_and_intersect_across_groups(): void
    {
        Job::where('employment_type', 'internship')->update(['seniority_level' => 'junior']);
        Job::where('employment_type', 'working_student')->update(['seniority_level' => 'senior']);
        $url = '/jobs?employment=internship%2Cworking_student&level=junior';
        $this->get('/jobs?level=junior&employment[]=working_student&employment[]=internship&min_salary=&tag=')->assertRedirect($url);
        $this->get($url)->assertOk()->assertSee('Startup Intern')->assertDontSee('Startup Student Associate');
        $this->get('/jobs?level[][]=junior')->assertNotFound();
    }

    public function test_job_lifecycle_structured_data_and_counters(): void
    {
        $job = Job::firstOrFail();
        $location = DB::table('locations')->insertGetId(['name' => 'London', 'slug' => 'london', 'type' => 'city', 'country_code' => 'GB']);
        $job->update(['primary_location_id' => $location, 'workplace_type' => 'onsite']);
        $path = '/jobs/'.$job->slug;
        $this->get('/jobs/london')->assertRedirect('/locations/london')->assertStatus(301);
        $this->get('/locations/london')->assertSee($job->title);
        $this->get($path)->assertOk()->assertSee('application/ld+json');
        $this->get($path.'/apply')->assertRedirect($job->apply_url)->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->assertDatabaseHas('job_metrics', ['job_id' => $job->id, 'views_count' => 1, 'apply_clicks_count' => 1]);
        $job->update(['expires_at' => now()->subSecond()]);
        $this->get($path)->assertGone();
        $this->get($path.'/apply')->assertNotFound();
        $job->update(['status' => 'draft']);
        $this->get($path)->assertNotFound();
    }

    public function test_filtered_urls_and_pair_threshold_follow_configuration(): void
    {
        config(['jobboard.min_jobs_to_index' => 2]);
        $this->get('/jobs?workplace=remote&tag=Ocean&page=1')->assertRedirect('/jobs?tag=Ocean&workplace=remote');
        $this->get('/jobs?min_salary=50001')->assertOk()->assertSee('No current openings.');
        $this->get('/jobs?min_salary=oops')->assertNotFound();
        $this->get('/jobs/internships/remote')->assertOk()->assertSee('noindex, follow');
        $this->get('/sitemaps/c.xml')->assertDontSee('jobs/internships/remote');
        $this->get('/salary-guide?page=2')->assertNotFound();
    }

    public function test_slug_registry_prevents_a_job_from_claiming_a_taxonomy_url(): void
    {
        $this->expectException(UniqueConstraintViolationException::class);
        Job::firstOrFail()->update(['slug' => 'internships']);
    }

    public function test_search_landings_and_sitemap_use_one_address_per_term(): void
    {
        config(['jobboard.markets' => ['Cryptocurrency']]);
        app(TaxonomyResolver::class)->sync();
        $this->get('/tags')->assertSee('href="http://localhost/ocean-jobs"', false);
        $this->get('/roles')->assertSee('/roles/demo-associate');
        $this->get('/jobs/ocean?page=2')->assertRedirect('/ocean-jobs?page=2')->assertStatus(301);
        $this->get('/ocean-jobs?page=2')->assertOk()->assertSee('rel="canonical" href="http://localhost/ocean-jobs?page=2"', false);
        $this->get('/remote-jobs')->assertOk()->assertSee('content="index, follow"', false);
        $this->get('/roles/demo-associate')->assertOk()->assertSee('Startup Specialist');
        $this->get('/collections/cryptocurrency')->assertOk()->assertSee('noindex, follow');
        $this->get('/some-unknown-jobs')->assertNotFound();
        $this->get('/roles/ocean')->assertNotFound();
        $this->get('/sitemaps/c.xml')->assertSee('/remote-jobs')->assertSee('/ocean-jobs')
            ->assertDontSee('/jobs/ocean</loc>', false)->assertDontSee('/collections/cryptocurrency');
        \App\Models\Company::firstOrFail()->update(['markets' => ['Cryptocurrency']]);
        $this->get('/collections/cryptocurrency')->assertOk()->assertSee('Startup Specialist')->assertSee('content="index, follow"', false);
    }

    public function test_company_salary_and_crawler_routes(): void
    {
        $this->get('/companies')->assertOk()->assertSee('companies/demo-studio');
        $this->get('/companies/demo-studio')->assertOk()->assertSee('Startup Specialist');
        $this->get('/salary-guide')->assertOk()->assertSee(config('jobboard.default_currency').' 50,000');
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /*?q=', false);
        $this->get('/llms.txt')->assertOk()->assertSee(config('jobboard.seo.llms_txt_intro'));
        $this->get('/sitemap.xml')->assertOk()->assertSee('sitemaps/a-0.xml');
        $this->get('/sitemaps/c.xml')->assertOk()->assertSee('jobs/internships/remote')->assertDontSee('jobs/contracts');
        $this->get('/sitemaps/a-0.xml')->assertOk()->assertSee('demo-internship-opening');
        $this->get('/sitemaps/a-999.xml')->assertNotFound();
    }
}
