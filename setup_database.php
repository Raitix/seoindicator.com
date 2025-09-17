<?php
// Database setup script for SEOIndicator.com
// This script creates the required database tables

require_once 'db_config.php';

echo "<h2>SEOIndicator.com Database Setup</h2>\n";

// Test database connection
$conn = get_db_connection();
if (!$conn) {
    echo "<p style='color: red;'>❌ Database connection failed. Please check your database configuration in db_config.php</p>\n";
    echo "<p>Make sure:</p>\n";
    echo "<ul>\n";
    echo "<li>MySQL server is running</li>\n";
    echo "<li>Database credentials are correct</li>\n";
    echo "<li>Database 'seoindicator' exists (or create it manually)</li>\n";
    echo "</ul>\n";
    exit;
}

echo "<p style='color: green;'>✅ Database connection successful!</p>\n";

// Read and execute the SQL setup script
$sql_file = __DIR__ . '/setup_database.sql';
if (!file_exists($sql_file)) {
    echo "<p style='color: red;'>❌ SQL setup file not found: $sql_file</p>\n";
    exit;
}

$sql_content = file_get_contents($sql_file);
if ($sql_content === false) {
    echo "<p style='color: red;'>❌ Could not read SQL setup file</p>\n";
    exit;
}

// Split SQL into individual statements
$statements = array_filter(array_map('trim', explode(';', $sql_content)));

$success_count = 0;
$error_count = 0;

foreach ($statements as $statement) {
    if (empty($statement) || strpos($statement, '--') === 0) {
        continue; // Skip empty statements and comments
    }
    
    if ($conn->query($statement)) {
        $success_count++;
        echo "<p style='color: green;'>✅ Executed: " . substr($statement, 0, 50) . "...</p>\n";
    } else {
        $error_count++;
        echo "<p style='color: red;'>❌ Error: " . $conn->error . "</p>\n";
        echo "<p style='color: red;'>Statement: " . htmlspecialchars($statement) . "</p>\n";
    }
}

$conn->close();

echo "<hr>\n";
echo "<h3>Setup Summary</h3>\n";
echo "<p>✅ Successful statements: $success_count</p>\n";
echo "<p>❌ Failed statements: $error_count</p>\n";

if ($error_count === 0) {
    echo "<p style='color: green; font-weight: bold;'>🎉 Database setup completed successfully!</p>\n";
    echo "<p>You can now use the SEO analysis tool. Try submitting a domain for analysis.</p>\n";
} else {
    echo "<p style='color: red; font-weight: bold;'>⚠️ Some errors occurred during setup. Please check the errors above.</p>\n";
}

echo "<hr>\n";
echo "<p><a href='index.php'>← Back to SEOIndicator.com</a></p>\n";
?>