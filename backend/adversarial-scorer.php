<?php

/**
 * Adversarial tests for OpportunityScorer
 * Run with: php adversarial-scorer.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\OpportunityScorer;

$scorer = new OpportunityScorer();
$failures = [];
$testsRun = 0;

function test(string $description, callable $testFn, array &$failures, int &$testsRun): void {
    $testsRun++;
    try {
        $result = $testFn();
        if ($result !== true) {
            $failures[] = "FAIL: $description - $result";
        } else {
            echo "✓ $description\n";
        }
    } catch (Throwable $e) {
        $failures[] = "EXCEPTION: $description - " . get_class($e) . ": " . $e->getMessage();
    }
}

echo "=== Adversarial Testing: OpportunityScorer ===\n\n";

// 1. Empty/whitespace edge cases
test("Empty string returns base score (50)", fn() => 
    $scorer->calculate('') === 50 ? true : "Got " . $scorer->calculate(''), $failures, $testsRun);

test("Whitespace only returns base score", fn() => 
    $scorer->calculate('   ') === 50 ? true : "Got " . $scorer->calculate('   '), $failures, $testsRun);

test("Tabs and newlines handled", fn() => 
    $scorer->calculate("\t\n\r") === 50 ? true : "Got " . $scorer->calculate("\t\n\r"), $failures, $testsRun);

// 2. Extremely long strings
$longKeyword = str_repeat('seo ', 1000); // 500 words
test("Very long keyword (500 words) doesn't crash", fn() => 
    $scorer->calculate($longKeyword) <= 100 ? true : "Score " . $scorer->calculate($longKeyword) . " exceeds 100", $failures, $testsRun);

// 3. Special characters
test("SQL injection attempt handled safely", fn() => 
    $scorer->calculate("'; DROP TABLE users; --") >= 50 ? true : "Unexpected score", $failures, $testsRun);

test("XSS attempt handled safely", fn() => 
    $scorer->calculate("<script>alert('xss')</script>") >= 50 ? true : "Unexpected score", $failures, $testsRun);

test("HTML entities handled", fn() => 
    $scorer->calculate("best &lt;seo&gt; tools") >= 50 ? true : "Unexpected score", $failures, $testsRun);

test("Quotes handled", fn() => 
    $scorer->calculate('best "seo" tools') >= 50 ? true : "Unexpected score", $failures, $testsRun);

test("Backslashes handled", fn() => 
    $scorer->calculate("best \\seo\\ tools") >= 50 ? true : "Unexpected score", $failures, $testsRun);

// 4. Unicode edge cases
test("Emoji handled", fn() => 
    $scorer->calculate("best seo tools 🚀") >= 50 ? true : "Unexpected score", $failures, $testsRun);

test("RTL text handled", fn() => 
    $scorer->calculate("مرحبا best tools") >= 50 ? true : "Unexpected score", $failures, $testsRun);

test("Chinese characters handled", fn() => 
    $scorer->calculate("最佳 seo 工具") >= 50 ? true : "Unexpected score", $failures, $testsRun);

test("Zero-width characters handled", fn() => 
    $scorer->calculate("best\u{200B}seo\u{200B}tools") >= 50 ? true : "Unexpected score", $failures, $testsRun);

// 5. Boundary tests
test("Score never below 50", fn() => {
    $keywords = ['', ' ', 'a', 'ab', 'abc', 'best', 'what'];
    foreach ($keywords as $kw) {
        if ($scorer->calculate($kw) < 50) {
            return "Score below 50 for '$kw': " . $scorer->calculate($kw);
        }
    }
    return true;
}, $failures, $testsRun);

test("Score never exceeds 100", fn() => {
    $keywords = [
        'how to choose the best seo tools for small business',
        'what is the top rated seo software for beginners',
        'why compare the best seo tools review guide',
    ];
    foreach ($keywords as $kw) {
        if ($scorer->calculate($kw) > 100) {
            return "Score above 100 for '$kw': " . $scorer->calculate($kw);
        }
    }
    return true;
}, $failures, $testsRun);

// 6. Question prefix edge cases
test("'what' alone should NOT trigger question bonus", fn() => 
    $scorer->calculate('what') === 50 ? true : "Got " . $scorer->calculate('what') . ", expected 50", $failures, $testsRun);

test("'what is' should trigger question bonus", fn() => 
    $scorer->calculate('what is seo') >= 65 ? true : "Got " . $scorer->calculate('what is seo'), $failures, $testsRun);

test("'whatsapp' should NOT trigger question bonus", fn() => 
    $scorer->calculate('whatsapp marketing') === 50 ? true : "Got " . $scorer->calculate('whatsapp marketing'), $failures, $testsRun);

test("'whois' should NOT trigger question bonus", fn() => 
    $scorer->calculate('whois lookup') === 50 ? true : "Got " . $scorer->calculate('whois lookup'), $failures, $testsRun);

// 7. Commercial intent edge cases
test("'bestie' should NOT trigger commercial bonus", fn() => 
    $scorer->calculate('bestie seo tips') === 50 ? true : "Got " . $scorer->calculate('bestie seo tips'), $failures, $testsRun);

test("'review' in middle triggers commercial", fn() => 
    $scorer->calculate('seo review tools') >= 60 ? true : "Got " . $scorer->calculate('seo review tools'), $failures, $testsRun);

test("'compare' triggers commercial", fn() => 
    $scorer->calculate('compare seo tools') >= 60 ? true : "Got " . $scorer->calculate('compare seo tools'), $failures, $testsRun);

// 8. Word count edge cases
test("Exactly 2 words: no long-tail bonus", fn() => 
    $scorer->calculate('seo tools') === 50 ? true : "Got " . $scorer->calculate('seo tools'), $failures, $testsRun);

test("Exactly 3 words: long-tail bonus (+10)", fn() => 
    $scorer->calculate('best seo tools') === 70 ? true : "Got " . $scorer->calculate('best seo tools') . ", expected 70", $failures, $testsRun);

test("Exactly 5 words: long-tail + very-long bonus (+15)", fn() => 
    $scorer->calculate('best seo tools for beginners') === 70 ? true : "Got " . $scorer->calculate('best seo tools for beginners') . ", expected 70", $failures, $testsRun);

// 9. Invokable tests
test("Invokable works correctly", fn() => {
    $s = new OpportunityScorer();
    return $s('best seo tools') === 70 ? true : "Got " . $s('best seo tools');
}, $failures, $testsRun);

// 10. Case sensitivity verification
test("MIXED CASE works same as lowercase", fn() => 
    $scorer->calculate('BEST SEO TOOLS') === $scorer->calculate('best seo tools') ? true : "Case mismatch", $failures, $testsRun);

echo "\n=== Results ===\n";
echo "Tests run: $testsRun\n";
echo "Failures: " . count($failures) . "\n";

if (count($failures) > 0) {
    echo "\nFailed tests:\n";
    foreach ($failures as $failure) {
        echo "  - $failure\n";
    }
    exit(1);
} else {
    echo "\n✓ All adversarial tests passed!\n";
    exit(0);
}
