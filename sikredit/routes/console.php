<?php

use Illuminate\Support\Facades\Schedule;

// Jalankan scheduler via cron: * * * * * php artisan schedule:run
Schedule::command('kredit:refresh')->dailyAt('00:30');
Schedule::command('kredit:reminders')->dailyAt('08:00');
