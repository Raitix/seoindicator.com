<?php
// Test script to verify configuration files are working
// This script checks if all required configuration is in place

echo "<h2>SEOIndicator.com Configuration Test</h2>\n";

// Test 1: Check if configuration files exist
echo "<h3>1. Configuration Files</h3>\n";

$config_files = ['db_config.php', 'config.php'];
foreach ($config_files as $file) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✅ $file exists</p>\n";
    } else {
        echo "<p style='color: red;'>❌ $file missing</p>\n";
    }
}

// Test 2: Include configuration files
echo "<h3>2. Configuration Loading</h3>\n";

try {
    require_once 'db_config.php';
    echo "<p style='color: green;'>✅ db_config.php loaded successfully</p>\n";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error loading db_config.php: " . $e->getMessage() . "</p>\n";
}

try {
    require_once 'config.php';
    echo "<p style='color: green;'>✅ config.php loaded successfully</p>\n";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error loading config.php: " . $e->getMessage() . "</p>\n";
}

// Test 3: Check if constants are defined
echo "<h3>3. Configuration Constants</h3>\n";

$required_constants = [
    'DB_HOST', 'DB_USER', 'DB_PASS', 'DB_NAME',
    'OPENROUTER_API_KEY', 'GOOGLE_PAGESPEED_API_KEY',
    'SITE_URL_FOR_OPENROUTER', 'SITE_NAME_FOR_OPENROUTER'
];

foreach ($required_constants as $constant) {
    if (defined($constant)) {
        $value = constant($constant);
        if (strpos($constant, 'KEY') !== false && $value === 'your_' . strtolower($constant) . '_here') {
            echo "<p style='color: orange;'>⚠️ $constant is defined but needs to be updated with actual API key</p>\n";
        } else {
            echo "<p style='color: green;'>✅ $constant is defined</p>\n";
        }
    } else {
        echo "<p style='color: red;'>❌ $constant is not defined</p>\n";
    }
}

// Test 4: Test database connection function
echo "<h3>4. Database Connection</h3>\n";

if (function_exists('get_db_connection')) {
    echo "<p style='color: green;'>✅ get_db_connection() function exists</p>\n";
    
    // Try to connect (this will fail if database doesn't exist, but that's expected)
    $conn = get_db_connection();
    if ($conn) {
        echo "<p style='color: green;'>✅ Database connection successful</p>\n";
        $conn->close();
    } else {
        echo "<p style='color: orange;'>⚠️ Database connection failed (this is expected if database/tables don't exist yet)</p>\n";
    }
} else {
    echo "<p style='color: red;'>❌ get_db_connection() function not found</p>\n";
}

// Test 5: Check main application files
echo "<h3>5. Main Application Files</h3>\n";

$app_files = ['index.php', 'seo_logic.php'];
foreach ($app_files as $file) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✅ $file exists</p>\n";
    } else {
        echo "<p style='color: red;'>❌ $file missing</p>\n";
    }
}

// Test 6: Check API endpoints
echo "<h3>6. API Endpoints</h3>\n";

$api_files = [
    'api/start-analysis.php',
    'api/run-indicator.php', 
    'api/job-progress.php',
    'api/cancel-job.php'
];

foreach ($api_files as $file) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✅ $file exists</p>\n";
    } else {
        echo "<p style='color: red;'>❌ $file missing</p>\n";
    }
}

echo "<hr>\n";
echo "<h3>Next Steps</h3>\n";
echo "<ol>\n";
echo "<li>Update API keys in config.php with your actual keys</li>\n";
echo "<li>Update database settings in db_config.php if needed</li>\n";
echo "<li>Run: <code>php setup_database.php</code> to create database tables</li>\n";
echo "<li>Test domain submission in your browser</li>\n";
echo "</ol>\n";

echo "<p><a href='index.php'>← Back to SEOIndicator.com</a></p>\n";
?>