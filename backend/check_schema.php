<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Check foreign keys
$fks = DB::table('INFORMATION_SCHEMA.KEY_COLUMN_USAGE')
    ->where('TABLE_SCHEMA', DB::getDatabaseName())
    ->where('TABLE_NAME', 'public_audit_views')
    ->whereNotNull('REFERENCED_TABLE_NAME')
    ->get();

echo "Foreign Keys: " . count($fks) . "\n";
foreach ($fks as $fk) {
    echo "Column: {$fk->COLUMN_NAME} -> {$fk->REFERENCED_TABLE_NAME}.{$fk->REFERENCED_COLUMN_NAME}\n";
}

// Check cascade delete
$cascade = DB::table('INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS')
    ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
    ->where('TABLE_NAME', 'public_audit_views')
    ->first();

if ($cascade) {
    echo "DELETE_RULE: {$cascade->DELETE_RULE}\n";
}

echo "\nAll indexes:\n";
$indexes = DB::select('SHOW INDEX FROM public_audit_views');
foreach ($indexes as $idx) {
    echo "- {$idx->Key_name} on {$idx->Column_name}\n";
}
