#!/usr/bin/env php
<?php

/**
 * Security Headers Testing Script
 * 
 * Tests security headers for:
 * - Content-Security-Policy
 * - X-Frame-Options
 * - X-Content-Type-Options
 * - Strict-Transport-Security
 * - X-XSS-Protection
 * - Referrer-Policy
 * - Permissions-Policy
 * - Cross-Origin policies
 */

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║       SECURITY HEADERS - COMPREHENSIVE TEST SUITE            ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Bootstrap Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use App\Http\Middleware\SecurityHeaders;

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

/**
 * Helper to test middleware response headers
 */
function testMiddleware(string $path = '/test', bool $isApi = false): \Symfony\Component\HttpFoundation\Response
{
    $request = Request::create($path, 'GET');
    
    if ($isApi) {
        $request->headers->set('Accept', 'application/json');
    }
    
    $middleware = new SecurityHeaders();
    
    return $middleware->handle($request, function($req) {
        return response()->json(['success' => true]);
    });
}

// =================================================================
// TEST SUITE 1: BASIC SECURITY HEADERS
// =================================================================

echo "┌──────────────────────────────────────────────────────────────┐\n";
echo "│ TEST SUITE 1: Basic Security Headers                        │\n";
echo "└──────────────────────────────────────────────────────────────┘\n";

runTest('SecurityHeaders middleware exists', function() {
    return class_exists(SecurityHeaders::class);
}, $results);

runTest('Config file exists', function() {
    $config = config('security-headers');
    return $config !== null;
}, $results);

runTest('X-Frame-Options header is set', function() {
    $response = testMiddleware();
    $header = $response->headers->get('X-Frame-Options');
    
    if (!$header) {
        return "X-Frame-Options header not found";
    }
    
    if ($header !== 'DENY') {
        return "Expected 'DENY', got '{$header}'";
    }
    
    return true;
}, $results);

runTest('X-Content-Type-Options header is set', function() {
    $response = testMiddleware();
    $header = $response->headers->get('X-Content-Type-Options');
    
    if (!$header) {
        return "X-Content-Type-Options header not found";
    }
    
    if ($header !== 'nosniff') {
        return "Expected 'nosniff', got '{$header}'";
    }
    
    return true;
}, $results);

runTest('X-XSS-Protection header is set', function() {
    $response = testMiddleware();
    $header = $response->headers->get('X-XSS-Protection');
    
    if (!$header) {
        return "X-XSS-Protection header not found";
    }
    
    if (!str_contains($header, '1') || !str_contains($header, 'block')) {
        return "Expected '1; mode=block', got '{$header}'";
    }
    
    return true;
}, $results);

runTest('Referrer-Policy header is set', function() {
    $response = testMiddleware();
    $header = $response->headers->get('Referrer-Policy');
    
    if (!$header) {
        return "Referrer-Policy header not found";
    }
    
    return true;
}, $results);

// =================================================================
// TEST SUITE 2: CONTENT SECURITY POLICY
// =================================================================

echo "\n";
echo "┌──────────────────────────────────────────────────────────────┐\n";
echo "│ TEST SUITE 2: Content Security Policy                       │\n";
echo "└──────────────────────────────────────────────────────────────┘\n";

runTest('CSP header is set for web pages', function() {
    $response = testMiddleware('/');
    $header = $response->headers->get('Content-Security-Policy');
    
    if (!$header) {
        return "Content-Security-Policy header not found";
    }
    
    return true;
}, $results);

runTest('CSP header is set for API endpoints', function() {
    $response = testMiddleware('/api/test', true);
    $header = $response->headers->get('Content-Security-Policy');
    
    if (!$header) {
        return "Content-Security-Policy header not found";
    }
    
    return true;
}, $results);

runTest('CSP contains default-src directive', function() {
    $response = testMiddleware('/');
    $header = $response->headers->get('Content-Security-Policy');
    
    if (!str_contains($header, 'default-src')) {
        return "CSP missing default-src directive";
    }
    
    return true;
}, $results);

runTest('CSP contains frame-ancestors directive', function() {
    $response = testMiddleware('/');
    $header = $response->headers->get('Content-Security-Policy');
    
    if (!str_contains($header, 'frame-ancestors')) {
        return "CSP missing frame-ancestors directive";
    }
    
    return true;
}, $results);

runTest('API CSP is more restrictive', function() {
    $webResponse = testMiddleware('/');
    $apiResponse = testMiddleware('/api/test', true);
    
    $webCsp = $webResponse->headers->get('Content-Security-Policy');
    $apiCsp = $apiResponse->headers->get('Content-Security-Policy');
    
    // API should have "default-src 'none'"
    if (!str_contains($apiCsp, "default-src 'none'")) {
        return "API CSP should be more restrictive (default-src 'none')";
    }
    
    return true;
}, $results);

// =================================================================
// TEST SUITE 3: ADDITIONAL SECURITY HEADERS
// =================================================================

echo "\n";
echo "┌──────────────────────────────────────────────────────────────┐\n";
echo "│ TEST SUITE 3: Additional Security Headers                   │\n";
echo "└──────────────────────────────────────────────────────────────┘\n";

runTest('Permissions-Policy header is set', function() {
    $response = testMiddleware();
    $header = $response->headers->get('Permissions-Policy');
    
    if (!$header) {
        return "Permissions-Policy header not found";
    }
    
    return true;
}, $results);

runTest('Cross-Origin-Embedder-Policy is set', function() {
    $response = testMiddleware();
    $header = $response->headers->get('Cross-Origin-Embedder-Policy');
    
    if (!$header) {
        return "Cross-Origin-Embedder-Policy header not found";
    }
    
    if ($header !== 'require-corp') {
        return "Expected 'require-corp', got '{$header}'";
    }
    
    return true;
}, $results);

runTest('Cross-Origin-Opener-Policy is set', function() {
    $response = testMiddleware();
    $header = $response->headers->get('Cross-Origin-Opener-Policy');
    
    if (!$header) {
        return "Cross-Origin-Opener-Policy header not found";
    }
    
    if ($header !== 'same-origin') {
        return "Expected 'same-origin', got '{$header}'";
    }
    
    return true;
}, $results);

runTest('Cross-Origin-Resource-Policy is set', function() {
    $response = testMiddleware();
    $header = $response->headers->get('Cross-Origin-Resource-Policy');
    
    if (!$header) {
        return "Cross-Origin-Resource-Policy header not found";
    }
    
    if ($header !== 'same-origin') {
        return "Expected 'same-origin', got '{$header}'";
    }
    
    return true;
}, $results);

runTest('X-Powered-By header is removed', function() {
    $response = testMiddleware();
    $header = $response->headers->get('X-Powered-By');
    
    if ($header !== null) {
        return "X-Powered-By header should be removed";
    }
    
    return true;
}, $results);

// =================================================================
// TEST SUITE 4: HEADER VALIDATION
// =================================================================

echo "\n";
echo "┌──────────────────────────────────────────────────────────────┐\n";
echo "│ TEST SUITE 4: Header Validation                             │\n";
echo "└──────────────────────────────────────────────────────────────┘\n";

runTest('All critical headers are present', function() {
    $response = testMiddleware();
    $headers = $response->headers;
    
    $required = [
        'Content-Security-Policy',
        'X-Frame-Options',
        'X-Content-Type-Options',
        'X-XSS-Protection',
        'Referrer-Policy',
        'Permissions-Policy',
    ];
    
    $missing = [];
    foreach ($required as $header) {
        if (!$headers->has($header)) {
            $missing[] = $header;
        }
    }
    
    if (!empty($missing)) {
        return "Missing headers: " . implode(', ', $missing);
    }
    
    return true;
}, $results);

runTest('Headers are properly formatted', function() {
    $response = testMiddleware();
    
    // Check CSP format
    $csp = $response->headers->get('Content-Security-Policy');
    if (!str_contains($csp, ';')) {
        return "CSP should contain semicolons between directives";
    }
    
    return true;
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
    echo "Status: ✅ ALL TESTS PASSED - SECURITY HEADERS READY!\n";
} else {
    echo "Status: ❌ TESTS FAILED - REVIEW ISSUES ABOVE\n";
}

echo "\n";

// Save report
$reportFile = __DIR__ . '/storage/logs/security_headers_test_report_' . date('Y-m-d_H-i-s') . '.txt';
$report = "SECURITY HEADERS TEST REPORT\n";
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
