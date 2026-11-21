<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomepageContent extends Model
{
    protected $fillable = ['id', 'settings'];
    protected $casts = ['settings' => 'array'];

    public const DEFAULTS = [
        'stack_enabled' => true, 'stack_title' => 'Search by your stack',
        'stack_description' => 'Jobs with your preferred tech stack.', 'stack_link' => 'All technologies',
        'stack_limit' => 12, 'technologies' => [],
        'latest_enabled' => true, 'latest_title' => 'Latest IT jobs',
        'latest_description' => 'The newest IT openings.', 'latest_link' => 'All jobs',
        'latest_limit' => 6, 'jobs' => [],
        'directory_enabled' => true, 'directory_title' => 'IT jobs by profession and location',
        'roles_title' => 'Top professions', 'locations_title' => 'Popular IT job locations',
        'roles_limit' => 20, 'locations_limit' => 12, 'roles' => [], 'locations' => [],
    ];

    public static function published(): array
    {
        return array_replace(self::DEFAULTS, self::find(1)?->settings ?? []);
    }
}
