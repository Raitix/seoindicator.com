<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Exporters\PdfReportExporter;

$exporter = new PdfReportExporter();
$report = ['name' => 'Test', 'sections' => ['cover']];
$data = [];
$branding = ['primaryColor' => '#2563eb', 'secondaryColor' => '#64748b'];

try {
    $pdf = $exporter->generate($report, $data, $branding);
    echo "Success: " . strlen($pdf) . " bytes\n";
    echo "PDF starts with: " . substr($pdf, 0, 20) . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}
