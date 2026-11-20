<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\DailyStat;
use App\Models\Department;
use App\Models\Job;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $niche = config('jobboard.niche_name');
            $tags = config('jobboard.featured_tags');
            $company = Company::firstOrCreate(['slug' => 'demo-studio'], ['name' => "$niche Studio", 'tagline' => "A demonstration team working in $niche."]);
            $department = Department::firstOrCreate(['slug' => 'demo-operations'], ['name' => "$niche Operations"]);
            $role = Role::firstOrCreate(['slug' => 'demo-associate'], ['name' => "$niche Associate", 'department_id' => $department->id]);
            foreach (['Intern' => 'internship', 'Student Associate' => 'working_student', 'Specialist' => 'full_time'] as $title => $type) {
                Job::firstOrCreate(['slug' => 'demo-'.str_replace('_', '-', $type).'-opening'], [
                    'company_id' => $company->id, 'department_id' => $department->id, 'role_id' => $role->id,
                    'title' => "$niche $title", 'description_plain' => "Work with the $niche team on practical projects.",
                    'employment_type' => $type, 'workplace_type' => 'remote', 'tags' => $tags, 'salary_currency' => config('jobboard.default_currency'),
                    'apply_url' => 'https://example.org/careers', 'source' => 'direct', 'status' => 'draft',
                ]);
            }
            for ($days = 1; $days <= 7; $days++) {
                DailyStat::firstOrCreate(['date' => today()->subDays($days)->toDateString()], [
                    'total_active_jobs' => 0, 'remote_jobs_count' => 0, 'top_roles' => [], 'top_tags' => [],
                ]);
            }
        });
    }
}
