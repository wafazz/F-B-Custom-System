<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Fire admin-scheduled push campaigns the moment they fall due.
// Output is appended to storage/logs/scheduler.log so you can confirm the
// cron is alive: `tail -f storage/logs/scheduler.log`.
Schedule::command('campaigns:dispatch')
    ->everyMinute()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/scheduler.log'));
