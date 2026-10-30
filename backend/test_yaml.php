<?php

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

try {
    $yaml = Yaml::parseFile('src/api/openapi.yaml');
    echo "Parsed successfully\n";
    echo "Has openapi key: " . (isset($yaml['openapi']) ? 'yes' : 'no') . "\n";
    echo "OpenAPI version: " . ($yaml['openapi'] ?? 'N/A') . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
