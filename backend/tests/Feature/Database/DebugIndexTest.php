<?php

namespace Tests\Feature\Database;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DebugIndexTest extends TestCase
{
    use RefreshDatabase;

    private string $tableName = 'user_onboarding';

    public function test_debug_indexes(): void
    {
        $pdo = $this->getConnection()->getPdo();
        $driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        
        echo "Driver: $driver\n";
        
        if ($driver === 'sqlite') {
            $result = $pdo->query("PRAGMA index_list({$this->tableName})")->fetchAll(\PDO::FETCH_ASSOC);
            echo "All indexes:\n";
            print_r($result);
            
            foreach ($result as $idx) {
                $columns = $pdo->query("PRAGMA index_info({$idx['name']})")->fetchAll(\PDO::FETCH_ASSOC);
                echo "Index {$idx['name']} columns:\n";
                print_r($columns);
            }
            
            // Check foreign keys
            $fkResult = $pdo->query("PRAGMA foreign_key_list({$this->tableName})")->fetchAll(\PDO::FETCH_ASSOC);
            echo "\nForeign keys:\n";
            print_r($fkResult);
            
            // Check if foreign keys are enabled
            $fkEnabled = $pdo->query("PRAGMA foreign_keys")->fetchColumn();
            echo "\nForeign keys enabled: $fkEnabled\n";
        }
        
        $this->assertTrue(true);
    }
}
