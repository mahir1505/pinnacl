<?php

use App\Jobs\SyncAllAccountsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sync all social media accounts daily
Schedule::job(new SyncAllAccountsJob)->dailyAt('03:00');
