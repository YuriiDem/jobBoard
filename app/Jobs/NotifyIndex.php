<?php

namespace App\Jobs;

use App\Models\Job;
use App\Services\Seo;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class NotifyIndex implements ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    public array $backoff = [30, 120, 600];

    public function __construct(public string $slug) {}

    public static function forSlug(string $slug): void
    {
        if (is_file(config('jobboard.seo.credentials') ?: '')) {
            static::dispatch($slug)->afterCommit();
        }
    }

    public function handle(): void
    {
        $path = config('jobboard.seo.credentials');
        if (! $path || ! is_file($path)) {
            return;
        }
        $credentials = app()->makeWith(ServiceAccountCredentials::class, ['scope' => 'https://www.googleapis.com/auth/indexing', 'jsonKey' => $path]);
        $token = $credentials->fetchAuthToken();
        Http::withToken($token['access_token'])->timeout(15)->post('https://indexing.googleapis.com/v3/urlNotifications:publish', [
            'url' => Seo::url('jobs/'.$this->slug),
            'type' => Job::active()->where('slug', $this->slug)->exists() ? 'URL_UPDATED' : 'URL_DELETED',
        ])->throw();
    }
}
