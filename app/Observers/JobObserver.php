<?php

namespace App\Observers;

use App\Jobs\NotifyIndex;
use App\Models\Job;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class JobObserver
{
    public function saving(Job $job): void
    {
        if ($job->status === 'published' && (! $job->exists || $job->isDirty('status'))) {
            $job->published_at ??= now();
            $job->expires_at ??= $job->published_at->copy()->addDays(config('jobboard.default_expiration_days'));
        }
    }

    public function saved(Job $job): void
    {
        if ($job->status !== 'published' && $job->getRawOriginal('status') !== 'published') {
            return;
        }
        $oldSlug = $job->getRawOriginal('slug');
        if ($oldSlug && $oldSlug !== $job->slug && $job->getRawOriginal('status') === 'published') {
            NotifyIndex::forSlug($oldSlug);
        }
        $this->refresh($job);
        if ($job->status === 'published' && $job->published_at?->isFuture() && is_file(config('jobboard.seo.credentials') ?: '')) {
            NotifyIndex::dispatch($job->slug)->delay($job->published_at)->afterCommit();
        }
    }

    public function deleted(Job $job): void
    {
        if ($job->status === 'published') {
            $this->refresh($job);
        }
    }

    private function refresh(Job $job): void
    {
        DB::afterCommit(fn () => self::forgetSalary());
        NotifyIndex::forSlug($job->slug);
    }

    public static function forgetSalary(): void
    {
        Cache::forget('salary.guide.'.config('jobboard.default_currency'));
        Cache::forget('salary.band.'.config('jobboard.default_currency'));
    }
}
