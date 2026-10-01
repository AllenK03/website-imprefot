<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Corre el día 1 de cada mes a las 00:05 AM hora Caracas
Schedule::command('inventory:close-month')->monthlyOn(1, '00:05')->timezone('America/Caracas');

Artisan::command('inspire', function () {$this->comment(Inspiring::quote());})->purpose('Display an inspiring quote');