<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\URL;

Artisan::command('jobboard:admin-login', function () {
    $this->line(URL::temporarySignedRoute('filament.admin.auth.login', now()->addMinutes(15)));
})->purpose('Generate a private 15-minute link to the Back Office sign-in form');

Schedule::command('jobboard:expire')->everyMinute()->withoutOverlapping();
Schedule::command('jobboard:snapshot')->dailyAt('23:55')->withoutOverlapping();
Schedule::command('jobboard:sitemap')->hourly()->withoutOverlapping();
