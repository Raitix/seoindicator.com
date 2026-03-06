<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$result = DB::select('SHOW CREATE TABLE badge_impressions');
echo $result[0]->{'Create Table'};
