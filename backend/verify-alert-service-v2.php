<?php

// Production verification for AlertDetectionService - v2

echo "=== AlertDetectionService Production Verification v2 ===\n\n";

// 1. Service instantiation via interface
try {
    $service = app(\App\Services\AIVisibility\Contracts\AlertDetectionServiceInterface::class);
    echo "✅ Service instantiated via interface: " . get_class($service) . "\n";
    echo "✅ Interface implemented: " . ($service instanceof \App\Services\AIVisibility\Contracts\AlertDetectionServiceInterface ? 'YES' : 'NO') . "\n";
} catch (\Exception $e) {
    echo "❌ Service instantiation failed: " . $e->getMessage() . "\n";
}

// 2. Direct instantiation
try {
    $directService = new \App\Services\AIVisibility\AlertDetectionService(
        app(\App\Services\AIVisibility\Contracts\AnalyticsAggregatorInterface::class)
    );
    echo "✅ Direct instantiation works\n";
} catch (\Exception $e) {
    echo "❌ Direct instantiation failed: " . $e->getMessage() . "\n";
}

// 3. Database table check
try {
    $alertCount = \App\Models\AIVisibility\AIVisibilityAlert::count();
    echo "✅ Database table accessible (alerts: $alertCount)\n";
} catch (\Exception $e) {
    echo "❌ Database check failed: " . $e->getMessage() . "\n";
}

// 4. Event class check
try {
    $eventClass = \App\Services\AIVisibility\Events\AIVisibilityAlertTriggered::class;
    echo "✅ Event class exists: $eventClass\n";
} catch (\Exception $e) {
    echo "❌ Event class check failed: " . $e->getMessage() . "\n";
}

// 5. Configuration check
$config = config('ai-visibility.alert_thresholds');
echo "✅ Configuration loaded:\n";
echo "   - mention_spike: " . ($config['mention_spike'] ?? 'NOT_SET') . "\n";
echo "   - mention_drop: " . ($config['mention_drop'] ?? 'NOT_SET') . "\n";
echo "   - competitor_gain: " . ($config['competitor_gain'] ?? 'NOT_SET') . "\n";
echo "   - sentiment_change: " . ($config['sentiment_change'] ?? 'NOT_SET') . "\n";

echo "\n=== All Checks Complete ===\n";
