<?php

use Illuminate\Support\Facades\Schedule;

// While the Yii and Laravel apps share the database: report new inconsistencies
// (a failed run shows up in Nightwatch). See deploy/README.md, "Parallel run".
Schedule::command('health:reconcile')->dailyAt('07:00');
