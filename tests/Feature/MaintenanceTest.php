<?php

namespace Tests\Feature;

use App\Filament\Resources\CompanyResource\Pages\ManageCompanies;
use App\Filament\Resources\DepartmentResource\Pages\ManageDepartments;
use App\Filament\Resources\JobResource\Pages\ManageJobs;
use App\Filament\Resources\LocationResource\Pages\ManageLocations;
use App\Filament\Resources\RoleResource\Pages\ManageRoles;
use App\Jobs\NotifyIndex;
use App\Models\Company;
use App\Models\DailyStat;
use App\Models\Department;
use App\Models\Job;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Filament\Facades\Filament;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class MaintenanceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_expiration_snapshot_and_publication_are_repeatable(): void
    {
        config(['jobboard.featured_tags' => ['Ocean']]);
        $this->seed(DatabaseSeeder::class);
        $jobs = Job::orderBy('id')->get();
        $jobs[0]->update(['status' => 'published', 'expires_at' => now()->subMinute()]);
        $jobs[1]->update(['status' => 'published', 'salary_period' => 'year', 'salary_currency' => 'USD', 'salary_min' => 90000]);
        Cache::put('salary.guide.USD', 100);
        foreach ([1, 2] as $run) {
            $this->artisan('jobboard:expire')->assertSuccessful();
            $this->artisan('jobboard:snapshot')->assertSuccessful();
            $this->artisan('jobboard:sitemap')->assertSuccessful();
        }
        $this->assertNull(Cache::get('salary.guide.USD'));
        $this->assertSame('expired', $jobs[0]->fresh()->status);
        $stat = DailyStat::whereDate('date', today())->sole();
        $this->assertEquals([1, 1, 90000], [$stat->total_active_jobs, $stat->remote_jobs_count, $stat->median_salary_usd]);
        $this->assertSame(['tag' => 'Ocean', 'jobs' => 1], $stat->top_tags[0]);
        $index = $this->get('/sitemap.xml')->assertOk()->getContent();
        preg_match_all('~/sitemaps/[a-f0-9]{64}\.xml~', $index, $matches);
        $this->assertCount(3, $matches[0]);
        $xml = '';
        foreach ($matches[0] as $path) {
            $xml .= $this->get($path)->assertOk()->getContent();
        }
        $this->assertStringContainsString('/jobs/'.$jobs[1]->slug, $xml);
        $this->assertStringNotContainsString('/jobs/'.$jobs[0]->slug, $xml);
        $this->assertStringNotContainsString('/jobs/'.$jobs[2]->slug, $xml);
    }

    public function test_index_notifications_and_cache_invalidation_follow_commit(): void
    {
        $this->seed(DatabaseSeeder::class);
        config(['queue.default' => 'database', 'queue.connections.database.table' => 'queue_jobs', 'jobboard.seo.credentials' => __FILE__]);
        $job = Job::firstOrFail();
        Cache::put('salary.guide.USD', 10);
        DB::beginTransaction();
        $job->update(['status' => 'published']);
        $this->assertDatabaseCount('queue_jobs', 0);
        DB::rollBack();
        $this->assertSame(10, Cache::get('salary.guide.USD'));
        $this->assertDatabaseCount('queue_jobs', 0);
        DB::transaction(fn () => $job->refresh()->update(['status' => 'published']));
        $this->assertNull(Cache::get('salary.guide.USD'));
        $this->assertDatabaseCount('queue_jobs', 1);
        config(['jobboard.seo.credentials' => null]);
        $job->delete();
        $this->assertDatabaseCount('queue_jobs', 1);
    }

    public function test_retried_index_message_uses_current_public_state(): void
    {
        $this->seed(DatabaseSeeder::class);
        $job = Job::firstOrFail();
        $job->update(['status' => 'published']);
        config(['jobboard.seo.credentials' => __FILE__]);
        $credentials = $this->createMock(ServiceAccountCredentials::class);
        $credentials->method('fetchAuthToken')->willReturn(['access_token' => 'test-token']);
        $this->app->bind(ServiceAccountCredentials::class, fn () => $credentials);
        Http::fake(['indexing.googleapis.com/*' => Http::response([])]);
        $notification = new NotifyIndex($job->slug);
        $notification->handle();
        Job::whereKey($job->id)->update(['status' => 'expired']);
        $notification->handle();
        $requests = Http::recorded();
        $this->assertSame('URL_UPDATED', $requests[0][0]['type']);
        $this->assertSame('URL_DELETED', $requests[1][0]['type']);
        $this->assertSame('http://localhost/jobs/'.$job->slug, $requests[1][0]['url']);
    }

    public function test_owner_can_manage_the_taxonomy_and_job_table_loads_its_relationships(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->seed(DatabaseSeeder::class);
        $department = Livewire::test(ManageDepartments::class);
        $department->callAction('create', data: ['name' => 'Research', 'slug' => 'research'])->assertHasNoActionErrors();
        $department->callAction('create', data: ['name' => 'Collision', 'slug' => 'demo-full-time-opening'])->assertHasActionErrors(['slug' => 'unique']);
        $category = Department::whereSlug('research')->firstOrFail();
        Livewire::test(ManageRoles::class)
            ->callAction('create', data: ['name' => 'Analyst', 'slug' => 'analyst', 'department_id' => $category->id])->assertHasNoActionErrors();
        Livewire::test(ManageLocations::class)
            ->callAction('create', data: ['name' => 'Italy', 'slug' => 'italy', 'type' => 'country', 'country_code' => 'IT'])->assertHasNoActionErrors();
        Livewire::test(ManageCompanies::class)
            ->callAction('create', data: ['name' => 'Ocean', 'slug' => 'ocean'])->assertHasNoActionErrors();
        $page = Livewire::test(ManageJobs::class);
        foreach ($page->instance()->getTableRecords() as $job) {
            $this->assertTrue($job->relationLoaded('company'));
            $this->assertTrue($job->relationLoaded('department'));
        }
    }

    public function test_owner_manages_jobs_and_non_owner_cannot_open_resources(): void
    {
        $this->actingAs(User::factory()->create());
        foreach (['jobs', 'roles', 'locations', 'departments', 'companies'] as $resource) {
            $this->get('/admin/'.$resource)->assertForbidden();
        }
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $company = Company::create(['name' => 'Ocean', 'slug' => 'ocean']);
        $page = Livewire::test(ManageJobs::class);
        $page->callAction('create', data: ['title' => 'Engineer', 'slug' => 'engineer', 'company_id' => $company->id, 'apply_url' => 'https://example.org/apply', 'status' => 'published'])->assertHasNoActionErrors();
        $job = Job::active()->sole();
        $page->assertCanSeeTableRecords([$job])->callTableAction('edit', $job, data: ['title' => 'Senior engineer'])->assertHasNoTableActionErrors();
        $this->get('/jobs/engineer')->assertSee('Senior engineer');
        $page->callTableAction('delete', $job);
        $this->assertModelMissing($job);
    }
}
