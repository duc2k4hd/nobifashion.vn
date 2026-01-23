<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule Flash Sale commands
Schedule::command('flash-sale:update-status')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('flash-sale:disable-sold-out')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// Schedule Voucher commands
Schedule::command('voucher:update-status')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();
