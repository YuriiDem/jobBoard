<?php

namespace App\Services;

use App\Models\Company;
use App\Models\DailyStat;
use App\Models\Job;
use Illuminate\Support\Str;

class Homepage
{
    public static function data(array $settings): array
    {
        $overview = Job::active()->toBase()->selectRaw("count(*) as jobs, count(distinct company_id) as companies, count(*) filter (where workplace_type='remote') as remote")->first();
        $history = DailyStat::where('date', '>=', today()->subDays(29))->orderBy('date')->get(['date', 'total_active_jobs']);
        $leaders = Company::withCount(['jobs' => fn ($q) => $q->active()])
            ->whereHas('jobs', fn ($q) => $q->active())->orderByDesc('jobs_count')->orderBy('id')->limit(5)->get();

        $options = ($settings['stack_enabled'] || $settings['directory_enabled'])
            ? JobFilterOptions::forListing(Job::active(), 'jobs') : [];
        $tags = $options['tag'] ?? [];
        $featured = config('jobboard.featured_tags');
        foreach ($tags as $tag => &$row) {
            $row['url'] = in_array($tag, $featured, true) ? '/'.Str::slug($tag).'-jobs' : '/jobs?'.http_build_query(['tag' => $tag], '', '&', PHP_QUERY_RFC3986);
        }
        unset($row);
        $technologies = self::pick($tags, $settings['technologies'], $settings['stack_limit']);
        $roles = self::pick($options['role'] ?? [], $settings['roles'], $settings['roles_limit']);
        $places = $options['location'] ?? [];
        if ($overview->remote) {
            $places['@remote'] = ['label' => 'Remote', 'count' => $overview->remote];
            uasort($places, fn ($a, $b) => $b['count'] <=> $a['count']);
        }
        $locations = self::pick($places, $settings['locations'], $settings['locations_limit']);
        $latest = $settings['latest_enabled'] ? Job::active()->listing()->addSelect('is_featured')
            ->when($settings['jobs'], fn ($q, $ids) => $q->whereIntegerInRaw('id', $ids))
            ->orderByDesc('published_at')->orderByDesc('id')->limit($settings['latest_limit'])->get() : collect();

        $heading = config('jobboard.site_name');
        $meta = Seo::meta($heading);
        return compact('heading', 'meta', 'overview', 'history', 'leaders', 'settings', 'technologies', 'latest', 'roles', 'locations');
    }

    private static function pick(array $rows, array $selected, int $limit): array
    {
        if ($selected) {
            $order = array_flip($selected);
            $rows = array_replace(array_intersect_key($order, $rows), array_intersect_key($rows, $order));
        }
        return array_slice($rows, 0, $limit, true);
    }
}
