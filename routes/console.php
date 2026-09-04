<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('inventory:close-month')->monthlyOn(now()->endOfMonth()->day, '23:59');

Artisan::command('inspire', function () {$this->comment(Inspiring::quote());})->purpose('Display an inspiring quote');
