<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Discover new motorcycle articles from active feeds once an hour.
Schedule::command('curvia:fetch-news')->hourly()->withoutOverlapping();

// Pace the heavy stages at one article per minute: first scrape the full body,
// then write the Polish post via DeepSeek. Keeps API load and the requests to
// source sites gentle.
Schedule::command('curvia:fetch-content --limit=1')->everyMinute()->withoutOverlapping();
Schedule::command('curvia:generate-posts --limit=1')->everyMinute()->withoutOverlapping();

// Auto-publish approved posts to Facebook at one random minute per time window.
Schedule::command('curvia:publish-facebook')->everyMinute()->withoutOverlapping();
