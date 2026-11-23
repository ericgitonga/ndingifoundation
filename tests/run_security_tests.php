<?php
/**
 * Test runner for Ndingi Foundation security & compliance unit tests.
 * Usage: php tests/run_security_tests.php
 */

declare(strict_types=1);

$SECURITY_TESTS = [];

require_once __DIR__ . '/test_security.php';

echo "Running Ndingi Security & Compliance Unit Tests...\n\n";

$passed = 0;
$failed = 0;
$start = microtime(true);

foreach ($SECURITY_TESTS as $name => $test_fn) {
    try {
        $test_fn();
        echo "  ✓ PASS: {$name}\n";
        $passed++;
    } catch (\Throwable $e) {
        echo "  ✗ FAIL: {$name}\n";
        echo "    " . $e->getMessage() . "\n";
        $failed++;
    }
}

$elapsed = round((microtime(true) - $start) * 1000, 2);
echo "\n========================================\n";
echo "Tests: {$passed} passed, {$failed} failed ({$elapsed}ms)\n";
echo "========================================\n";

if ($failed > 0) {
    exit(1);
}

exit(0);
