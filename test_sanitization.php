#!/usr/bin/env php
<?php

/**
 * Sanitization System Testing Script
 * 
 * Tests input sanitization for:
 * - XSS prevention
 * - SQL injection prevention
 * - Command injection prevention
 */

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║      INPUT SANITIZATION - COMPREHENSIVE TEST SUITE           ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Bootstrap Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\SanitizationService;

// Test tracking
$results = [
    'total' => 0,
    'passed' => 0,
    'failed' => 0,
    'tests' => []
];

/**
 * Run a test
 */
function runTest(string $name, callable $test, array &$results): void
{
    $results['total']++;
    echo str_pad("Testing: $name", 70, '.');
    
    try {
        $result = $test();
        
        if ($result === true) {
            echo " ✓ PASS\n";
            $results['passed']++;
            $results['tests'][$name] = 'PASS';
        } else {
            echo " ✗ FAIL\n";
            $results['failed']++;
            $results['tests'][$name] = 'FAIL: ' . $result;
            if (is_string($result)) {
                echo "      Reason: " . $result . "\n";
            }
        }
    } catch (\Exception $e) {
        echo " ✗ ERROR\n";
        $results['failed']++;
        $results['tests'][$name] = 'ERROR: ' . $e->getMessage();
        echo "      Error: " . $e->getMessage() . "\n";
    }
}

$sanitizer = app(SanitizationService::class);

// =================================================================
// TEST SUITE 1: XSS PREVENTION
// =================================================================

echo "┌──────────────────────────────────────────────────────────────┐\n";
echo "│ TEST SUITE 1: XSS Prevention                                 │\n";
echo "└──────────────────────────────────────────────────────────────┘\n";

runTest('Detect <script> tag', function() use ($sanitizer) {
    return $sanitizer->containsXss('<script>alert("XSS")</script>');
}, $results);

runTest('Detect javascript: protocol', function() use ($sanitizer) {
    return $sanitizer->containsXss('<a href="javascript:alert(1)">Click</a>');
}, $results);

runTest('Detect onclick handler', function() use ($sanitizer) {
    return $sanitizer->containsXss('<div onclick="alert(1)">Click</div>');
}, $results);

runTest('Sanitize <script> tags', function() use ($sanitizer) {
    $input = '<script>alert("XSS")</script>Hello';
    $output = $sanitizer->sanitizeString($input);
    return !str_contains($output, '<script');
}, $results);

runTest('Sanitize event handlers', function() use ($sanitizer) {
    $input = '<div onclick="alert(1)">Hello</div>';
    $output = $sanitizer->sanitizeString($input);
    return !str_contains($output, 'onclick');
}, $results);

runTest('Strip HTML tags completely', function() use ($sanitizer) {
    $input = '<b>Hello</b>';
    $output = $sanitizer->sanitizeString($input);
    // strip_tags() is called first, so tags are removed before htmlspecialchars()
    return $output === 'Hello';
}, $results);

// =================================================================
// TEST SUITE 2: SQL INJECTION PREVENTION
// =================================================================

echo "\n";
echo "┌──────────────────────────────────────────────────────────────┐\n";
echo "│ TEST SUITE 2: SQL Injection Prevention                       │\n";
echo "└──────────────────────────────────────────────────────────────┘\n";

runTest('Detect UNION SELECT', function() use ($sanitizer) {
    return $sanitizer->containsSqlInjection("' UNION SELECT * FROM users--");
}, $results);

runTest('Detect OR 1=1', function() use ($sanitizer) {
    return $sanitizer->containsSqlInjection("admin' OR '1'='1");
}, $results);

runTest('Detect DROP TABLE', function() use ($sanitizer) {
    return $sanitizer->containsSqlInjection("'; DROP TABLE users--");
}, $results);

runTest('Detect SQL comments', function() use ($sanitizer) {
    return $sanitizer->containsSqlInjection("admin'--");
}, $results);

runTest('Allow safe string without SQL', function() use ($sanitizer) {
    return !$sanitizer->containsSqlInjection("John O'Brien");
}, $results);

runTest('Sanitize SQL injection attempt', function() use ($sanitizer) {
    $input = "' OR 1=1--";
    $output = $sanitizer->sanitizeString($input);
    return $output !== $input; // Should be modified
}, $results);

// =================================================================
// TEST SUITE 3: COMMAND INJECTION PREVENTION
// =================================================================

echo "\n";
echo "┌──────────────────────────────────────────────────────────────┐\n";
echo "│ TEST SUITE 3: Command Injection Prevention                   │\n";
echo "└──────────────────────────────────────────────────────────────┘\n";

runTest('Detect pipe operator', function() use ($sanitizer) {
    return $sanitizer->containsCommandInjection("test | cat /etc/passwd");
}, $results);

runTest('Detect semicolon command separator', function() use ($sanitizer) {
    return $sanitizer->containsCommandInjection("test; rm -rf /");
}, $results);

runTest('Detect backticks', function() use ($sanitizer) {
    return $sanitizer->containsCommandInjection("test`whoami`");
}, $results);

runTest('Detect command substitution', function() use ($sanitizer) {
    return $sanitizer->containsCommandInjection("test$(whoami)");
}, $results);

runTest('Allow safe command-like string', function() use ($sanitizer) {
    return !$sanitizer->containsCommandInjection("test-file-name.txt");
}, $results);

// =================================================================
// TEST SUITE 4: DATA TYPE SANITIZATION
// =================================================================

echo "\n";
echo "┌──────────────────────────────────────────────────────────────┐\n";
echo "│ TEST SUITE 4: Data Type Sanitization                        │\n";
echo "└──────────────────────────────────────────────────────────────┘\n";

runTest('Sanitize email', function() use ($sanitizer) {
    $input = '  TEST@EXAMPLE.COM  ';
    $output = $sanitizer->sanitizeEmail($input);
    return $output === 'test@example.com';
}, $results);

runTest('Reject invalid email', function() use ($sanitizer) {
    $input = 'not-an-email';
    $output = $sanitizer->sanitizeEmail($input);
    return $output === null;
}, $results);

runTest('Sanitize integer with min/max', function() use ($sanitizer) {
    $output = $sanitizer->sanitizeInt(150, 1, 100);
    return $output === 100; // Clamped to max
}, $results);

runTest('Sanitize float', function() use ($sanitizer) {
    $output = $sanitizer->sanitizeFloat('12.34abc');
    return $output === 12.34;
}, $results);

runTest('Sanitize boolean from string', function() use ($sanitizer) {
    $true = $sanitizer->sanitizeBool('true');
    $false = $sanitizer->sanitizeBool('false');
    return $true === true && $false === false;
}, $results);

runTest('Sanitize URL', function() use ($sanitizer) {
    $input = 'https://example.com/path?query=value';
    $output = $sanitizer->sanitizeUrl($input);
    return $output === $input;
}, $results);

runTest('Reject javascript: URL', function() use ($sanitizer) {
    $input = 'javascript:alert(1)';
    $output = $sanitizer->sanitizeUrl($input);
    return $output === null;
}, $results);

// =================================================================
// TEST SUITE 5: FILENAME & PATH SANITIZATION
// =================================================================

echo "\n";
echo "┌──────────────────────────────────────────────────────────────┐\n";
echo "│ TEST SUITE 5: Filename & Path Sanitization                  │\n";
echo "└──────────────────────────────────────────────────────────────┘\n";

runTest('Sanitize filename', function() use ($sanitizer) {
    $input = 'my file.txt';
    $output = $sanitizer->sanitizeFilename($input);
    return $output === 'my_file.txt';
}, $results);

runTest('Prevent directory traversal in filename', function() use ($sanitizer) {
    $input = '../../etc/passwd';
    $output = $sanitizer->sanitizeFilename($input);
    return $output === 'passwd'; // basename only
}, $results);

runTest('Remove null bytes from filename', function() use ($sanitizer) {
    $input = "test\0file.txt";
    $output = $sanitizer->sanitizeFilename($input);
    return !str_contains($output, "\0");
}, $results);

runTest('Prevent path traversal', function() use ($sanitizer) {
    $input = '../../../etc/passwd';
    $output = $sanitizer->sanitizePath($input);
    return !str_contains($output, '../');
}, $results);

// =================================================================
// TEST SUITE 6: ARRAY SANITIZATION
// =================================================================

echo "\n";
echo "┌──────────────────────────────────────────────────────────────┐\n";
echo "│ TEST SUITE 6: Array Sanitization                            │\n";
echo "└──────────────────────────────────────────────────────────────┘\n";

runTest('Sanitize array values', function() use ($sanitizer) {
    $input = ['<script>alert(1)</script>', 'normal text'];
    $output = $sanitizer->sanitizeArray($input);
    return !str_contains($output[0], '<script');
}, $results);

runTest('Sanitize nested arrays', function() use ($sanitizer) {
    $input = [
        'user' => [
            'name' => '<b>John</b>',
            'email' => '  test@example.com  '
        ]
    ];
    $output = $sanitizer->sanitizeArray($input);
    return !str_contains($output['user']['name'], '<b');
}, $results);

runTest('Sanitize array keys', function() use ($sanitizer) {
    $input = ['<script>key</script>' => 'value'];
    $output = $sanitizer->sanitizeArray($input);
    $keys = array_keys($output);
    return !str_contains($keys[0], '<script');
}, $results);

// =================================================================
// TEST SUITE 7: VALIDATION HELPER
// =================================================================

echo "\n";
echo "┌──────────────────────────────────────────────────────────────┐\n";
echo "│ TEST SUITE 7: Validation Helper                             │\n";
echo "└──────────────────────────────────────────────────────────────┘\n";

runTest('Validate clean input', function() use ($sanitizer) {
    $result = $sanitizer->validateInput('Hello World');
    return $result['valid'] === true;
}, $results);

runTest('Detect XSS in validation', function() use ($sanitizer) {
    $result = $sanitizer->validateInput('<script>alert(1)</script>');
    return $result['valid'] === false && in_array('xss', $result['threats']);
}, $results);

runTest('Detect SQL injection in validation', function() use ($sanitizer) {
    $result = $sanitizer->validateInput("' OR 1=1--");
    return $result['valid'] === false && in_array('sql_injection', $result['threats']);
}, $results);

runTest('Detect multiple threats', function() use ($sanitizer) {
    $result = $sanitizer->validateInput('<script>alert(1)</script> OR 1=1');
    return count($result['threats']) >= 2;
}, $results);

// =================================================================
// GENERATE REPORT
// =================================================================

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║                      TEST RESULTS SUMMARY                     ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";

$passRate = $results['total'] > 0 ? round(($results['passed'] / $results['total']) * 100, 1) : 0;

echo "Total Tests:    " . $results['total'] . "\n";
echo "Passed:         " . $results['passed'] . " ✓\n";
echo "Failed:         " . $results['failed'] . ($results['failed'] > 0 ? " ✗" : "") . "\n";
echo "Pass Rate:      " . $passRate . "%\n";
echo "\n";

if ($results['failed'] === 0) {
    echo "Status: ✅ ALL TESTS PASSED - SANITIZATION SYSTEM READY!\n";
} else {
    echo "Status: ❌ TESTS FAILED - REVIEW ISSUES ABOVE\n";
}

echo "\n";

// Save report
$reportFile = __DIR__ . '/storage/logs/sanitization_test_report_' . date('Y-m-d_H-i-s') . '.txt';
$report = "INPUT SANITIZATION TEST REPORT\n";
$report .= "Generated: " . date('Y-m-d H:i:s') . "\n";
$report .= str_repeat("=", 70) . "\n\n";
$report .= "SUMMARY\n-------\n";
$report .= "Total Tests: {$results['total']}\n";
$report .= "Passed: {$results['passed']}\n";
$report .= "Failed: {$results['failed']}\n";
$report .= "Pass Rate: {$passRate}%\n\n";
$report .= str_repeat("=", 70) . "\n\n";
$report .= "DETAILED RESULTS\n----------------\n";

foreach ($results['tests'] as $name => $result) {
    $report .= "\n[$result] $name\n";
}

file_put_contents($reportFile, $report);
echo "Detailed report saved to: $reportFile\n\n";

exit($results['failed'] > 0 ? 1 : 0);
