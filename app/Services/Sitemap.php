<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Job;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

class Sitemap
{
    public function __construct(private TaxonomyResolver $taxonomy) {}

    private function xml(string $root, string $element, iterable $paths): string
    {
        $xml = new \XMLWriter;
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElementNS(null, $root, 'http://www.sitemaps.org/schemas/sitemap/0.9');
        foreach ($paths as $path) {
            $xml->startElement($element);
            $xml->writeElement('loc', Seo::url($path));
            $xml->endElement();
        }
        $xml->endDocument();

        return $xml->outputMemory();
    }

    private function sources(): array
    {
        return ['a' => Job::active(), 'b' => Company::whereHas('jobs', fn ($query) => $query->active())];
    }

    private function hubPaths(): iterable
    {
        $taxonomy = $this->taxonomy;
        yield from ['', 'companies', 'salary-guide'];
        if (Job::active()->exists()) {
            yield 'jobs';
        }
        foreach (DB::table('public_slugs')->where('kind', '!=', 'jobs')->lazyById(250, 'slug') as $term) {
            $query = Job::active();
            $taxonomy->constrain($query, $term);
            if ($query->exists()) {
                yield 'jobs/'.$term->slug;
            }
        }
        foreach (array_unique(config('jobboard.approved_pairs')) as $pair) {
            $parts = explode('/', $pair);
            if (count($parts) !== 2) {
                continue;
            }
            $topic = $taxonomy->find($parts[0]);
            $location = $taxonomy->find($parts[1]);
            if (! $topic || ! $location || $topic->kind === 'jobs' || $taxonomy->isLocation($topic) || ! $taxonomy->isLocation($location)) {
                continue;
            }
            $query = Job::active();
            $taxonomy->constrain($query, $topic);
            $taxonomy->constrain($query, $location);
            if ($taxonomy->eligible($query)) {
                yield 'jobs/'.$pair;
            }
        }
    }

    public function publicationShards(): iterable
    {
        foreach (LazyCollection::make(fn () => $this->hubPaths())->chunk(1000) as $paths) {
            yield $this->xml('urlset', 'url', $paths);
        }
        foreach ($this->parts() as $part) {
            if ($part !== 'c') {
                yield $this->part($part);
            }
        }
    }

    public function parts(): iterable
    {
        yield 'c';
        foreach ($this->sources() as $key => $query) {
            foreach ($query->selectRaw('(id / 1000) as bucket')->distinct()->orderBy('bucket')->cursor() as $row) {
                yield "$key-$row->bucket";
            }
        }

    }

    public function index(iterable $paths): string
    {
        return $this->xml('sitemapindex', 'sitemap', $paths);
    }

    public function part(string $part): string
    {
        if ($part === 'c') {
            $paths = $this->hubPaths();
        } else {
            abort_unless(preg_match('/^([ab])-(0|[1-9][0-9]{0,12})$/D', $part, $match), 404);
            $start = (int) $match[2] * 1000;
            $prefix = $match[1] === 'a' ? 'jobs/' : 'companies/';
            $paths = $this->sources()[$match[1]]->whereBetween('id', [$start, $start + 999])->orderBy('id')->pluck('slug')->map(fn ($slug) => $prefix.$slug);
            abort_if($paths->isEmpty(), 404);
        }

        return $this->xml('urlset', 'url', $paths);
    }
}
