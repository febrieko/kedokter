<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Pengingat H-1 Pukul 17:00 WIB Otomatis Setiap Hari
Schedule::command('clinic:send-h1-reminders')
    ->dailyAt('17:00')
    ->withoutOverlapping();
