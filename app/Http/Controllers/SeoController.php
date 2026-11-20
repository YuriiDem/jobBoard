<?php

namespace App\Http\Controllers;

use App\Services\Seo;
use App\Services\Sitemap;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SeoController extends Controller
{
    public function robots(): Response
    {
        $lines = ['User-agent: *'];
        foreach (config('jobboard.filters') as $filter) {
            $lines[] = "Disallow: /*?$filter=";
            $lines[] = "Disallow: /*?*&$filter=";
        }
        $lines[] = 'Sitemap: '.Seo::url('sitemap.xml');

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    public function llms(): Response
    {
        return response('# '.config('jobboard.site_name')."\n\n".config('jobboard.seo.llms_txt_intro')."\n\n- Jobs: ".Seo::url('jobs')."\n- Companies: ".Seo::url('companies')."\n- Salary guide: ".Seo::url('salary-guide')."\n", 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    public function sitemap(Sitemap $sitemap): Response
    {
        $xml = Cache::get('sitemap.index') ?? $sitemap->index(collect($sitemap->parts())->map(fn ($part) => "sitemaps/$part.xml"));

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    public function part(string $part, Sitemap $sitemap): Response
    {
        if (preg_match('/^[a-f0-9]{64}$/D', $part)) {
            $xml = Cache::get('sitemap.part.'.$part);
            abort_if($xml === null, 404);
        } else {
            $xml = $sitemap->part($part);
        }

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
