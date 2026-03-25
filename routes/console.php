<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('sync:yandex-auto')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('sync:amocrm-auto')
    ->everyFifteenMinutes()
    ->withoutOverlapping();
