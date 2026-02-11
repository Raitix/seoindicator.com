<?php
require_once 'db_config.php';
require_once 'config.php';
require_once __DIR__ . '/seo-guides/Indicator.php';
require_once __DIR__ . '/seo-guides/UrlIndicator.php';
require_once __DIR__ . '/seo-guides/MetaDescriptionIndicator.php';

use SEOIndicator\UrlIndicator;
use SEOIndicator\MetaDescriptionIndicator;

function analyze_indicator(string $indicatorName, array $data) {
    $indicatorClasses = [
        'url' => UrlIndicator::class,
        'meta_description' => MetaDescriptionIndicator::class
    ];

    if (!isset($indicatorClasses[$indicatorName])) {
        throw new Exception("Indicator '$indicatorName' not found.");
    }

    $indicator = new $indicatorClasses[$indicatorName]();
    return $indicator->analyze($data);
}

function analyze_all_indicators(array $data) {
    $results = [];
    foreach (['url', 'meta_description'] as $indicatorName) {
        $results[$indicatorName] = analyze_indicator($indicatorName, $data);
    }
    return $results;
}

?>