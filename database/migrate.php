<?php
/**
 * Database Migration Tool for SEOIndicator.com
 * 
 * This script manages database schema changes between local and production environments.
 * It provides a safe way to apply, rollback, and track database migrations.
 */

// Include database configuration
require_once __DIR__ . '/../db_config.php';

class DatabaseMigrator {
    private $conn;
    private $migrationsDir;
    private $logsDir;
    
    public function __construct() {
        $this->migrationsDir = __DIR__ . '/migrations';
        $this->logsDir = __DIR__ . '/logs';
        
        // Create directories if they don't exist
        if (!is_dir($this->migrationsDir)) {
            mkdir($this->migrationsDir, 0755, true);
        }
        if (!is_dir($this->logsDir)) {
            mkdir($this->logsDir, 0755, true);
        }
        
        $this->connect();
        $this->createMigrationsTable();
    }
    
    private function connect() {
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
        
        // Set charset
        $this->conn->set_charset("utf8mb4");
    }
    
    private function createMigrationsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            version VARCHAR(20) NOT NULL UNIQUE,
            filename VARCHAR(255) NOT NULL,
            applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(version)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $this->conn->query($sql);
    }
    
    public function getStatus() {
        echo "=== Database Migration Status ===\n";
        
        // Get applied migrations
        $result = $this->conn->query("SELECT version, filename, applied_at FROM migrations ORDER BY version");
        $applied = [];
        while ($row = $result->fetch_assoc()) {
            $applied[$row['version']] = $row;
        }
        
        // Get available migration files
        $files = glob($this->migrationsDir . '/*.sql');
        $available = [];
        foreach ($files as $file) {
            $filename = basename($file);
            if (preg_match('/^(\d+)_(.+)\.sql$/', $filename, $matches)) {
                $version = $matches[1];
                $available[$version] = $filename;
            }
        }
        
        echo "Applied migrations:\n";
        foreach ($applied as $version => $info) {
            echo "  ✓ {$version} - {$info['filename']} (applied: {$info['applied_at']})\n";
        }
        
        echo "\nPending migrations:\n";
        $pending = false;
        foreach ($available as $version => $filename) {
            if (!isset($applied[$version])) {
                echo "  ⏳ {$version} - {$filename}\n";
                $pending = true;
            }
        }
        
        if (!$pending) {
            echo "  No pending migrations\n";
        }
        
        return $applied;
    }
    
    public function migrate($targetVersion = null) {
        echo "=== Running Database Migrations ===\n";
        
        // Get applied migrations
        $result = $this->conn->query("SELECT version FROM migrations ORDER BY version");
        $applied = [];
        while ($row = $result->fetch_assoc()) {
            $applied[] = $row['version'];
        }
        
        // Get available migration files
        $files = glob($this->migrationsDir . '/*.sql');
        $migrations = [];
        foreach ($files as $file) {
            $filename = basename($file);
            if (preg_match('/^(\d+)_(.+)\.sql$/', $filename, $matches)) {
                $version = $matches[1];
                $migrations[$version] = $file;
            }
        }
        
        ksort($migrations);
        
        $appliedCount = 0;
        foreach ($migrations as $version => $file) {
            if (in_array($version, $applied)) {
                continue; // Already applied
            }
            
            if ($targetVersion && $version > $targetVersion) {
                break; // Stop at target version
            }
            
            echo "Applying migration {$version}...\n";
            
            if ($this->applyMigration($version, $file)) {
                $appliedCount++;
                echo "  ✓ Successfully applied {$version}\n";
            } else {
                echo "  ✗ Failed to apply {$version}\n";
                break;
            }
        }
        
        if ($appliedCount > 0) {
            echo "\n✓ Applied {$appliedCount} migration(s) successfully\n";
        } else {
            echo "\n✓ No migrations to apply\n";
        }
    }
    
    public function rollback($steps = 1) {
        echo "=== Rolling Back Migrations ===\n";
        
        // Get last applied migrations
        $result = $this->conn->query("SELECT version, filename FROM migrations ORDER BY version DESC LIMIT " . intval($steps));
        $toRollback = [];
        while ($row = $result->fetch_assoc()) {
            $toRollback[] = $row;
        }
        
        if (empty($toRollback)) {
            echo "No migrations to rollback\n";
            return;
        }
        
        foreach ($toRollback as $migration) {
            echo "Rolling back {$migration['version']}...\n";
            
            // Look for rollback file
            $rollbackFile = $this->migrationsDir . '/' . $migration['version'] . '_rollback.sql';
            
            if (file_exists($rollbackFile)) {
                if ($this->executeSqlFile($rollbackFile)) {
                    // Remove from migrations table
                    $stmt = $this->conn->prepare("DELETE FROM migrations WHERE version = ?");
                    $stmt->bind_param("s", $migration['version']);
                    $stmt->execute();
                    $stmt->close();
                    
                    echo "  ✓ Successfully rolled back {$migration['version']}\n";
                } else {
                    echo "  ✗ Failed to rollback {$migration['version']}\n";
                    break;
                }
            } else {
                echo "  ⚠ No rollback file found for {$migration['version']}\n";
                echo "  Manual rollback required\n";
            }
        }
    }
    
    private function applyMigration($version, $file) {
        $this->conn->begin_transaction();
        
        try {
            // Execute the migration
            if (!$this->executeSqlFile($file)) {
                throw new Exception("Failed to execute migration file");
            }
            
            // Record the migration
            $filename = basename($file);
            $stmt = $this->conn->prepare("INSERT INTO migrations (version, filename) VALUES (?, ?)");
            $stmt->bind_param("ss", $version, $filename);
            $stmt->execute();
            $stmt->close();
            
            $this->conn->commit();
            return true;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            $this->logError("Migration {$version} failed: " . $e->getMessage());
            return false;
        }
    }
    
    private function executeSqlFile($file) {
        $sql = file_get_contents($file);
        
        if ($sql === false) {
            $this->logError("Could not read file: {$file}");
            return false;
        }
        
        // Split by semicolon and execute each statement
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            if (empty($statement)) continue;
            
            if (!$this->conn->query($statement)) {
                $this->logError("SQL Error: " . $this->conn->error . "\nStatement: " . $statement);
                return false;
            }
        }
        
        return true;
    }
    
    private function logError($message) {
        $logFile = $this->logsDir . '/migration_errors.log';
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($logFile, "[{$timestamp}] {$message}\n", FILE_APPEND | LOCK_EX);
    }
    
    public function backup($filename = null) {
        if (!$filename) {
            $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        }
        
        $backupDir = __DIR__ . '/backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $backupFile = $backupDir . '/' . $filename;
        
        echo "Creating database backup: {$backupFile}\n";
        
        // Use mysqldump if available
        $command = "mysqldump -h " . DB_HOST . " -u " . DB_USER . " -p" . DB_PASS . " " . DB_NAME . " > " . $backupFile;
        
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0) {
            echo "✓ Backup created successfully\n";
            return $backupFile;
        } else {
            echo "✗ Backup failed\n";
            return false;
        }
    }
}

// Command line interface
if (php_sapi_name() === 'cli') {
    $migrator = new DatabaseMigrator();
    
    $args = $argv;
    array_shift($args); // Remove script name
    
    if (empty($args)) {
        echo "Usage: php migrate.php [command] [options]\n";
        echo "Commands:\n";
        echo "  status                    - Show migration status\n";
        echo "  migrate [version]         - Apply pending migrations\n";
        echo "  rollback [steps]          - Rollback last N migrations\n";
        echo "  backup [filename]         - Create database backup\n";
        exit(1);
    }
    
    $command = $args[0];
    
    switch ($command) {
        case 'status':
            $migrator->getStatus();
            break;
            
        case 'migrate':
            $version = isset($args[1]) ? $args[1] : null;
            $migrator->migrate($version);
            break;
            
        case 'rollback':
            $steps = isset($args[1]) ? intval($args[1]) : 1;
            $migrator->rollback($steps);
            break;
            
        case 'backup':
            $filename = isset($args[1]) ? $args[1] : null;
            $migrator->backup($filename);
            break;
            
        default:
            echo "Unknown command: {$command}\n";
            exit(1);
    }
}
?>
