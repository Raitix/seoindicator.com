<?php
/**
 * Database Migration Script for SEOIndicator.com
 * Handles applying and tracking database migrations
 */

// Include database configuration
require_once __DIR__ . '/../db_config.php';

class DatabaseMigrator {
    private $conn;
    private $migrationsTable = 'migrations';

    public function __construct() {
        $this->connect();
        $this->ensureMigrationsTable();
    }

    private function connect() {
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error . "\n");
        }

        echo "Connected to database successfully.\n";
    }

    private function ensureMigrationsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->migrationsTable} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL,
            applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_migration (migration)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        if (!$this->conn->query($sql)) {
            die("Error creating migrations table: " . $this->conn->error . "\n");
        }
    }

    public function status() {
        echo "=== Database Migration Status ===\n";

        $appliedMigrations = $this->getAppliedMigrations();
        $availableMigrations = $this->getAvailableMigrations();

        echo "Applied migrations:\n";
        if (empty($appliedMigrations)) {
            echo "  No migrations applied yet.\n";
        } else {
            foreach ($appliedMigrations as $migration) {
                echo "  ✓ {$migration['migration']} (applied: {$migration['applied_at']})\n";
            }
        }

        echo "\nPending migrations:\n";
        $pendingMigrations = array_diff($availableMigrations, array_column($appliedMigrations, 'migration'));
        if (empty($pendingMigrations)) {
            echo "  No pending migrations.\n";
        } else {
            foreach ($pendingMigrations as $migration) {
                echo "  ○ $migration\n";
            }
        }

        echo "\n";
    }

    public function migrate($targetVersion = null) {
        $availableMigrations = $this->getAvailableMigrations();
        $appliedMigrations = $this->getAppliedMigrations();

        $migrationsToApply = [];

        foreach ($availableMigrations as $migration) {
            if (in_array($migration, array_column($appliedMigrations, 'migration'))) {
                continue;
            }

            if ($targetVersion && strpos($migration, $targetVersion . '_') !== 0) {
                continue;
            }

            $migrationsToApply[] = $migration;
        }

        if (empty($migrationsToApply)) {
            echo "No migrations to apply.\n";
            return;
        }

        echo "Applying migrations:\n";
        foreach ($migrationsToApply as $migration) {
            echo "  Applying $migration...\n";
            if ($this->applyMigration($migration)) {
                echo "  ✓ $migration applied successfully.\n";
            } else {
                echo "  ✗ Failed to apply $migration.\n";
                exit(1);
            }
        }

        echo "All migrations applied successfully!\n";
    }

    public function rollback($steps = 1) {
        $appliedMigrations = $this->getAppliedMigrations();

        if (empty($appliedMigrations)) {
            echo "No migrations to rollback.\n";
            return;
        }

        $migrationsToRollback = array_slice($appliedMigrations, -$steps);

        echo "Rolling back migrations:\n";
        foreach ($migrationsToRollback as $migration) {
            echo "  Rolling back {$migration['migration']}...\n";
            if ($this->rollbackMigration($migration['migration'])) {
                echo "  ✓ {$migration['migration']} rolled back successfully.\n";
            } else {
                echo "  ✗ Failed to rollback {$migration['migration']}.\n";
                exit(1);
            }
        }

        echo "Rollback completed!\n";
    }

    private function getAvailableMigrations() {
        $migrations = [];
        $dir = __DIR__ . '/migrations/';

        if (!is_dir($dir)) {
            die("Migrations directory not found: $dir\n");
        }

        $files = scandir($dir);
        foreach ($files as $file) {
            if (preg_match('/^(\d+)_.+\.sql$/', $file, $matches)) {
                $migrations[] = $file;
            }
        }

        sort($migrations);
        return $migrations;
    }

    private function getAppliedMigrations() {
        $sql = "SELECT migration, applied_at FROM {$this->migrationsTable} ORDER BY id ASC";
        $result = $this->conn->query($sql);

        if (!$result) {
            return [];
        }

        $migrations = [];
        while ($row = $result->fetch_assoc()) {
            $migrations[] = $row;
        }

        return $migrations;
    }

    private function applyMigration($migrationFile) {
        $filePath = __DIR__ . '/migrations/' . $migrationFile;

        if (!file_exists($filePath)) {
            echo "Migration file not found: $filePath\n";
            return false;
        }

        $sql = file_get_contents($filePath);

        if (!$sql) {
            echo "Could not read migration file: $filePath\n";
            return false;
        }

        // Split by semicolon and execute each statement
        $statements = array_filter(array_map('trim', explode(';', $sql)));

        foreach ($statements as $statement) {
            if (empty($statement) || strpos($statement, '--') === 0) {
                continue;
            }

            if (!$this->conn->query($statement)) {
                echo "Error executing migration: " . $this->conn->error . "\n";
                echo "Statement: $statement\n";
                return false;
            }
        }

        // Record the migration
        $sql = "INSERT INTO {$this->migrationsTable} (migration) VALUES (?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $migrationFile);

        if (!$stmt->execute()) {
            echo "Error recording migration: " . $this->conn->error . "\n";
            return false;
        }

        return true;
    }

    private function rollbackMigration($migrationFile) {
        $rollbackFile = __DIR__ . '/migrations/' . preg_replace('/\.sql$/', '_rollback.sql', $migrationFile);

        if (!file_exists($rollbackFile)) {
            echo "Rollback file not found: $rollbackFile\n";
            return false;
        }

        $sql = file_get_contents($rollbackFile);

        if (!$sql) {
            echo "Could not read rollback file: $rollbackFile\n";
            return false;
        }

        // Split by semicolon and execute each statement
        $statements = array_filter(array_map('trim', explode(';', $sql)));

        foreach ($statements as $statement) {
            if (empty($statement) || strpos($statement, '--') === 0) {
                continue;
            }

            if (!$this->conn->query($statement)) {
                echo "Error executing rollback: " . $this->conn->error . "\n";
                echo "Statement: $statement\n";
                return false;
            }
        }

        // Remove the migration record
        $sql = "DELETE FROM {$this->migrationsTable} WHERE migration = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $migrationFile);

        if (!$stmt->execute()) {
            echo "Error removing migration record: " . $this->conn->error . "\n";
            return false;
        }

        return true;
    }
}

// Command line interface
if (PHP_SAPI === 'cli') {
    $migrator = new DatabaseMigrator();

    if ($argc < 2) {
        $migrator->status();
        echo "Usage: php migrate.php [status|migrate|rollback] [--version=X] [--steps=N]\n";
        echo "  status          Show migration status\n";
        echo "  migrate         Apply all pending migrations\n";
        echo "  migrate --version=X  Apply migration starting with version X\n";
        echo "  rollback        Rollback last migration\n";
        echo "  rollback --steps=N  Rollback last N migrations\n";
        exit(0);
    }

    $command = $argv[1];

    switch ($command) {
        case 'status':
        case '--status':
            $migrator->status();
            break;

        case 'migrate':
            $targetVersion = null;
            for ($i = 2; $i < $argc; $i++) {
                if (strpos($argv[$i], '--version=') === 0) {
                    $targetVersion = str_replace('--version=', '', $argv[$i]);
                }
            }
            $migrator->migrate($targetVersion);
            break;

        case 'rollback':
            $steps = 1;
            for ($i = 2; $i < $argc; $i++) {
                if (strpos($argv[$i], '--steps=') === 0) {
                    $steps = (int) str_replace('--steps=', '', $argv[$i]);
                }
            }
            $migrator->rollback($steps);
            break;

        default:
            echo "Unknown command: $command\n";
            echo "Usage: php migrate.php [status|migrate|rollback] [--version=X] [--steps=N]\n";
            exit(1);
    }
}

?>