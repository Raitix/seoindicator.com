<?php

require __DIR__ . '/vendor/autoload.php';

$ref = new ReflectionClass(\App\Services\AIVisibility\AnalyticsAggregator::class);
echo 'Class: ' . $ref->getName() . PHP_EOL;
echo 'Implements: ' . PHP_EOL;
foreach ($ref->getInterfaceNames() as $interface) {
    echo '  - ' . $interface . PHP_EOL;
}

echo PHP_EOL . 'Method getTrend signature:' . PHP_EOL;
$method = $ref->getMethod('getTrend');
echo '  ' . $method->getDeclaringClass()->getName() . '::getTrend(';
$params = $method->getParameters();
$first = true;
foreach ($params as $param) {
    if (!$first) echo ', ';
    $type = $param->getType() ? $param->getType()->getName() : 'mixed';
    echo $type . ' $' . $param->getName();
    if ($param->isDefaultValueAvailable()) {
        echo ' = ' . var_export($param->getDefaultValue(), true);
    }
    $first = false;
}
echo ')' . PHP_EOL;

// Check interface
$interfaceRef = new ReflectionClass(\App\Services\AIVisibility\Contracts\AnalyticsAggregatorInterface::class);
echo PHP_EOL . 'Interface getTrend signature:' . PHP_EOL;
$interfaceMethod = $interfaceRef->getMethod('getTrend');
echo '  ' . $interfaceMethod->getDeclaringClass()->getName() . '::getTrend(';
$params = $interfaceMethod->getParameters();
$first = true;
foreach ($params as $param) {
    if (!$first) echo ', ';
    $type = $param->getType() ? $param->getType()->getName() : 'mixed';
    echo $type . ' $' . $param->getName();
    if ($param->isDefaultValueAvailable()) {
        echo ' = ' . var_export($param->getDefaultValue(), true);
    }
    $first = false;
}
echo ')' . PHP_EOL;
