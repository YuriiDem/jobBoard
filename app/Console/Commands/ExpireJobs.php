<?php

namespace App\Console\Commands;

use App\Jobs\NotifyIndex;
use App\Observers\JobObserver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireJobs extends Command
{
    protected $signature = 'jobboard:expire';

    protected $description = 'Expire published jobs past their closing time';

    public function handle(): int
    {
        $cutoff = now();
        $lastId = 0;
        do {
            $rows = DB::transaction(function () use ($cutoff, $lastId) {
                $rows = DB::table('jobs')->where('status', 'published')->where('expires_at', '<=', $cutoff)->where('id', '>', $lastId)->orderBy('id')->limit(250)->lockForUpdate()->get(['id', 'slug']);
                if ($rows->isNotEmpty()) {
                    DB::table('jobs')->whereIntegerInRaw('id', $rows->pluck('id'))->update(['status' => 'expired', 'updated_at' => now()]);
                    DB::afterCommit(fn () => JobObserver::forgetSalary());
                    foreach ($rows as $row) {
                        NotifyIndex::forSlug($row->slug);
                    }
                }

                return $rows;
            });
            $lastId = $rows->last()?->id ?? $lastId;
        } while ($rows->count() === 250);

        return self::SUCCESS;
    }
}
