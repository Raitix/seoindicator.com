<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$before = now();
usleep(10000); // 10ms
$analyzed = now();
usleep(10000); // 10ms
$after = now();

echo 'Before: ' . $before->toDateTimeString('microsecond') . PHP_EOL;
echo 'Analyzed: ' . $analyzed->toDateTimeString('microsecond') . PHP_EOL;
echo 'After: ' . $after->toDateTimeString('microsecond') . PHP_EOL;
echo 'Between: ' . ($analyzed->between($before, $after) ? 'true' : 'false') . PHP_EOL;
