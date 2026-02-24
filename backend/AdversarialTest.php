<?php

namespace Tests;

use App\Services\QueryMatcher;
use Illuminate\Support\Str;

class AdversarialTest
{
    public static function run()
    {
        $matcher = app(QueryMatcher::class);

        // Example of adversarial test cases
        $cases = [
            ['input1' => 'simple test', 'input2' => 'SIMPLE TEST', 'expected' => 1.0], // Case-insensitivity
            ['input1' => '!@#$', 'input2' => '123', 'expected' => 0.0], // Special character mismatch
            ['input1' => Str::random(10000), 'input2' => Str::random(10000), 'expected' => 0.0], // Input overflow
        ];

        foreach ($cases as $case) {
            $result = $matcher->calculateSimilarity($case['input1'], $case['input2']);
            echo "Input: ({$case['input1']}, {$case['input2']}), Result: $result - Expected: {$case['expected']}\n";
        }
    }
}

// Run tests
AdversarialTest::run();