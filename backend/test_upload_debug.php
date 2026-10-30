<?php

// Test what's happening with logo upload
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Http\UploadedFile;

// Create a minimal PNG
$pngContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
$tempPath = sys_get_temp_dir() . '/test_logo.png';
file_put_contents($tempPath, $pngContent);

$file = new UploadedFile(
    $tempPath,
    'test_logo.png',
    null,
    null,
    true
);

echo "=== Test 1: Without padding ===" . PHP_EOL;
echo "File size: " . filesize($tempPath) . PHP_EOL;
echo "File MIME type: " . $file->getMimeType() . PHP_EOL;

// Test with padding
$pngContent2 = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
$pngContent2 .= str_repeat("\0", 100000); // Pad with null bytes

$tempPath2 = sys_get_temp_dir() . '/test_logo2.png';
file_put_contents($tempPath2, $pngContent2);

$file2 = new UploadedFile(
    $tempPath2,
    'test_logo2.png',
    null,
    null,
    true
);

echo PHP_EOL . "=== Test 2: With padding ===" . PHP_EOL;
echo "File size: " . filesize($tempPath2) . PHP_EOL;
echo "File MIME type: " . $file2->getMimeType() . PHP_EOL;

// Test validation for both
$validator1 = Validator::make(
    ['logo' => $file],
    ['logo' => 'required|file|mimes:png,jpg,jpeg,svg|max:2048']
);

$validator2 = Validator::make(
    ['logo' => $file2],
    ['logo' => 'required|file|mimes:png,jpg,jpeg,svg|max:2048']
);

echo PHP_EOL . "=== Validation ===" . PHP_EOL;
echo "Without padding: " . ($validator1->fails() ? 'FAILED: ' . implode(', ', $validator1->errors()->all()) : 'PASSED') . PHP_EOL;
echo "With padding: " . ($validator2->fails() ? 'FAILED: ' . implode(', ', $validator2->errors()->all()) : 'PASSED') . PHP_EOL;

unlink($tempPath);
unlink($tempPath2);
