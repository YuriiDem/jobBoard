<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('jobboard:expire')->everyMinute()->withoutOverlapping();
Schedule::command('jobboard:snapshot')->dailyAt('23:55')->withoutOverlapping();
Schedule::command('jobboard:sitemap')->hourly()->withoutOverlapping();
