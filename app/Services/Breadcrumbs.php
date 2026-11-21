<?php

namespace App\Services;

use Illuminate\Support\Str;

class Breadcrumbs
{
    public static function forPage(string $path, string $heading, ?string $termKind = null): array
    {
        if ($path === '/') {
            return [];
        }

        $items = [['name' => 'Home', 'url' => Seo::url('/')]];
        $section = explode('/', $path, 2)[0];
        $directory = array_search($termKind, TaxonomyResolver::DIRECTORIES, true);

        if ($section === 'companies' && $path !== 'companies') {
            $items[] = ['name' => 'Companies', 'url' => Seo::url('companies')];
        } elseif ($path !== 'jobs' && ($section === 'jobs' || $termKind || isset(TaxonomyResolver::DIRECTORIES[$section]))) {
            $items[] = ['name' => 'Jobs', 'url' => Seo::url('jobs')];
        }

        if ($directory !== false) {
            $items[] = ['name' => Str::headline($directory), 'url' => Seo::url($directory)];
        }

        $name = match ($path) {
            'jobs' => 'Jobs',
            'companies' => 'Companies',
            default => isset(TaxonomyResolver::DIRECTORIES[$path]) ? Str::headline($path) : $heading,
        };
        $items[] = ['name' => $name, 'url' => null];

        return $items;
    }
}
