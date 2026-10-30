<?php
require __DIR__ . '/vendor/autoload.php';

echo "Testing DomCrawler loading...\n";

if (class_exists('Symfony\Component\DomCrawler\Crawler')) {
    echo "Crawler class exists!\n";
    $crawler = new Symfony\Component\DomCrawler\Crawler('<html><body>Test</body></html>');
    echo "Crawler created successfully!\n";
} else {
    echo "Crawler class NOT found.\n";
    
    // Check if the file exists
    $file = __DIR__ . '/vendor/symfony/dom-crawler/Crawler.php';
    echo "File exists: " . (file_exists($file) ? 'yes' : 'no') . "\n";
    
    // Check autoload_psr4
    $loader = require __DIR__ . '/vendor/autoload.php';
    $prefixes = $loader->getPrefixesPsr4();
    if (isset($prefixes['Symfony\\Component\\DomCrawler\\'])) {
        echo "DomCrawler prefix found in autoload.\n";
    } else {
        echo "DomCrawler prefix NOT found in autoload.\n";
        echo "Available Symfony prefixes:\n";
        foreach ($prefixes as $prefix => $paths) {
            if (strpos($prefix, 'Symfony') !== false) {
                echo "  - $prefix\n";
            }
        }
    }
}
