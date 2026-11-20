<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Pages\Auth\Login;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class FoundationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_repeated_setup_preserves_credentials_and_customized_demo_records(): void
    {
        $directory = sys_get_temp_dir().'/board-test-'.Str::uuid();
        File::makeDirectory($directory, 0700);
        $this->app->useEnvironmentPath($directory);
        $secret = Str::random(40);
        File::put($directory.'/password', $secret);
        config(['jobboard.niche_name' => 'Ocean', 'jobboard.featured_tags' => ['Cartography'], 'jobboard.default_currency' => 'EUR',
            'jobboard.admin_email' => 'owner@example.test', 'jobboard.admin_password_file' => $directory.'/password']);
        try {
            $this->artisan('jobboard:install', ['--no-interaction' => true, '--demo' => true])->assertSuccessful();
            $owner = User::whereEmail('owner@example.test')->firstOrFail();
            $hash = $owner->password;
            File::put($directory.'/password', Str::random(40));
            $this->artisan('jobboard:install', ['--no-interaction' => true, '--demo' => true])->assertSuccessful();
            $this->assertSame($hash, $owner->refresh()->password);
            $this->assertDatabaseCount('jobs', 3);
            $this->assertDatabaseCount('daily_stats', 7);
            $this->assertDatabaseHas('jobs', ['title' => 'Ocean Intern', 'salary_currency' => 'EUR']);
            $this->assertDatabaseHas('public_slugs', ['slug' => 'cartography', 'kind' => 'tags']);
            Filament::setCurrentPanel(Filament::getPanel('admin'));
            Livewire::test(Login::class)->fillForm(['email' => $owner->email, 'password' => $secret])->call('authenticate')->assertHasNoFormErrors();
            $this->assertAuthenticatedAs($owner);
            $this->get('/admin')->assertOk();
        } finally {
            File::deleteDirectory($directory);
        }
    }

    public function test_regular_account_cannot_access_the_panel(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
    }
}
