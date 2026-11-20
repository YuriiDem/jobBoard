<?php

namespace App\Console\Commands;

use App\Services\Sitemap;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PublishSitemap extends Command
{
    protected $signature = 'jobboard:sitemap';

    protected $description = 'Publish immutable sitemap shards and replace the index';

    public function handle(Sitemap $sitemap): int
    {
        $index = DB::transaction(function () use ($sitemap) {
            if (DB::transactionLevel() === 1) {
                DB::statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
            }
            $paths = [];
            foreach ($sitemap->publicationShards() as $xml) {
                $digest = hash('sha256', $xml);
                if (! Cache::put('sitemap.part.'.$digest, $xml, now()->addDays(2))) {
                    throw new \RuntimeException('Could not store sitemap shard.');
                }
                $paths[] = "sitemaps/$digest.xml";
            }

            return $sitemap->index($paths);
        });

        return Cache::put('sitemap.index', $index, now()->addHours(2)) ? self::SUCCESS : self::FAILURE;
    }
}
