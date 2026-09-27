<?php

use Illuminate\Support\Facades\Schedule;

// While the Yii and Laravel apps share the database: report new inconsistencies
// (a failed run shows up in Nightwatch). See deploy/README.md, "Parallel run".
Schedule::command('health:reconcile')->dailyAt('07:00');

// Activity log entries older than activitylog.clean_after_days
Schedule::command('activitylog:clean --force')->dailyAt('02:30');
