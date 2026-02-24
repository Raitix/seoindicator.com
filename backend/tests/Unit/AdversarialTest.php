<?php

namespace Tests\Unit;

use App\Services\QueryMatcher;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdversarialTest extends TestCase
{
    public function testAdversarialCases()
    {
        $matcher = app(QueryMatcher::class);

        $cases = [
            ['input1' => 'simple test', 'input2' => 'SIMPLE TEST', 'expected' => 1.0], // Case-insensitivity
            ['input1' => '!@#$', 'input2' => '123', 'expected' => 0.0], // Special character mismatch
            ['input1' => Str::random(10000), 'input2' => Str::random(10000), 'expected' => 0.0], // Input overflow
        ];

        foreach ($cases as $case) {
            $result = $matcher->calculateSimilarity($case['input1'], $case['input2']);
            $this->assertSame(
                $case['expected'],
                $result,
                sprintf("Failed on inputs: %s vs %s", $case['input1'], $case['input2'])
            );
        }
    }
}