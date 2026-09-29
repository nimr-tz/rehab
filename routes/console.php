<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('notifications:cleanup-expired')->dailyAt('02:30');
