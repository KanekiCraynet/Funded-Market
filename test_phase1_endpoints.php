#!/usr/bin/env php
<?php

/**
 * Phase 1 Endpoint Testing Script
 * 
 * Tests all newly created endpoints:
 * - QuantController (3 endpoints)
 * - SentimentController (2 endpoints)
 * - CircuitBreakerService
 */

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║         PHASE 1 - ENDPOINT TESTING SUITE                     ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Bootstrap Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Test tracking
$results = [
    'total' => 0,
    'passed' => 0,
    'failed' => 0,
    'skipped' => 0,
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
        } elseif ($result === null) {
            echo " ⚠ SKIP\n";
            $results['skipped']++;
            $results['tests'][$name] = 'SKIP';
        } else {
            echo " ✗ FAIL\n";
            $results['failed']++;
            $results['tests'][$name] = 'FAIL: ' . $result;
        }
    } catch (\Exception $e) {
        echo " ✗ ERROR\n";
        $results['failed']++;
        $results['tests'][$name] = 'ERROR: ' . $e->getMessage();
    }
}

// =================================================================
// TEST SUITE 1: SERVICE REGISTRATION
// =================================================================

echo "\n";
echo "┌──────────────────────────────────────────────────────────────┐\n";
echo "│ TEST SUITE 1: Service Registration                          │\n";
echo "└──────────────────────────────────────────────────────────────┘\n";

runTest('QuantController can be instantiated', function() {
    try {
        $controller = app(\App\Http\Controllers\Api\V1\QuantController::class);
        return $controller !== null;
    } catch (\Exception $e) {
        return "Failed to instantiate: " . $e->getMessage();
    }
}, $results);

runTest('SentimentController can be instantiated', function() {
    try {
        $controller = app(\App\Http\Controllers\Api\V1\SentimentController::class);
        return $controller !== null;
    } catch (\Exception $e) {
        return "Failed to instantiate: " . $e->getMessage();
    }
}, $results);

runTest('CircuitBreakerService is registered', function() {
    try {
        $service = app(\App\Domain\Shared\Services\CircuitBreakerService::class);
        return $service !== null;
    } catch (\Exception $e) {
        return "Service not registered: " . $e->getMessage();
    }
}, $results);

runTest('InstrumentService is available', function() {
    try {
        $service = app(\App\Domain\Market\Services\InstrumentService::class);
        return $service !== null;
    } catch (\Exception $e) {
        return "Service not available: " . $e->getMessage();
    }
}, $results);

// =================================================================
// TEST SUITE 2: QUANTCONTROLLER METHODS
// =================================================================

echo "\n";
echo "┌──────────────────────────────────────────────────────────────┐\n";
echo "│ TEST SUITE 2: QuantController Methods                       │\n";
echo "└──────────────────────────────────────────────────────────────┘\n";

runTest('QuantController has indicators method', function() {
    $controller = app(\App\Http\Controllers\Api\V1\QuantController::class);
    return method_exists($controller, 'indicators');
}, $results);

runTest('QuantController has trends method', function() {
    $controller = app(\App\Http\Controllers\Api\V1\QuantController::class);
    return method_exists($controller, 'trends');
}, $results);

runTest('QuantController has volatility method', function() {
    $controller = app(\App\Http\Controllers\Api\V1\QuantController::class);
    return method_exists($controller, 'volatility');
}, $results);

// Test with actual instrument if available
runTest('QuantController indicators() works with valid symbol', function() {
    $instrument = \App\Domain\Market\Models\Instrument::first();
    
    if (!$instrument) {
        return null; // Skip if no instruments
    }
    
    $controller = app(\App\Http\Controllers\Api\V1\QuantController::class);
    $request = \Illuminate\Http\Request::create('/test', 'GET');
    
    try {
        $response = $controller->indicators($instrument->symbol, $request);
        $data = json_decode($response->getContent(), true);
        
        // Check response structure
        if (!isset($data['success'])) {
            return "Missing 'success' field";
        }
        
        if ($data['success'] && !isset($data['data'])) {
            return "Missing 'data' field on success";
        }
        
        return true;
    } catch (\Exception $e) {
        return "Exception: " . $e->getMessage();
    }
}, $results);

runTest('QuantController returns 404 for invalid symbol', function() {
    $controller = app(\App\Http\Controllers\Api\V1\QuantController::class);
    $request = \Illuminate\Http\Request::create('/test', 'GET');
    
    try {
        $response = $controller->indicators('INVALIDSYMBOL123', $request);
        
        // Should return 404
        if ($response->getStatusCode() !== 404) {
            return "Expected 404, got " . $response->getStatusCode();
        }
        
        $data = json_decode($response->getContent(), true);
        
        if ($data['success'] !== false) {
            return "Expected success=false for 404";
        }
        
        return true;
    } catch (\Exception $e) {
        return "Exception: " . $e->getMessage();
    }
}, $results);

// =================================================================
// TEST SUITE 3: SENTIMENTCONTROLLER METHODS
// =================================================================

echo "\n";
echo "┌──────────────────────────────────────────────────────────────┐\n";
echo "│ TEST SUITE 3: SentimentController Methods                   │\n";
echo "└──────────────────────────────────────────────────────────────┘\n";

runTest('SentimentController has show method', function() {
    $controller = app(\App\Http\Controllers\Api\V1\SentimentController::class);
    return method_exists($controller, 'show');
}, $results);

runTest('SentimentController has news method', function() {
    $controller = app(\App\Http\Controllers\Api\V1\SentimentController::class);
    return method_exists($controller, 'news');
}, $results);

runTest('SentimentController show() works with valid symbol', function() {
    $instrument = \App\Domain\Market\Models\Instrument::first();
    
    if (!$instrument) {
        return null; // Skip if no instruments
    }
    
    $controller = app(\App\Http\Controllers\Api\V1\SentimentController::class);
    $request = \Illuminate\Http\Request::create('/test', 'GET');
    
    try {
        $response = $controller->show($instrument->symbol, $request);
        $data = json_decode($response->getContent(), true);
        
        // Check response structure
        if (!isset($data['success'])) {
            return "Missing 'success' field";
        }
        
        return true;
    } catch (\Exception $e) {
        return "Exception: " . $e->getMessage();
    }
}, $results);

runTest('SentimentController returns 404 for invalid symbol', function() {
    $controller = app(\App\Http\Controllers\Api\V1\SentimentController::class);
    $request = \Illuminate\Http\Request::create('/test', 'GET');
    
    try {
        $response = $controller->show('INVALIDSYMBOL123', $request);
        
        if ($response->getStatusCode() !== 404) {
            return "Expected 404, got " . $response->getStatusCode();
        }
        
        return true;
    } catch (\Exception $e) {
        return "Exception: " . $e->getMessage();
    }
}, $results);

// =================================================================
// TEST SUITE 4: CIRCUIT BREAKER
// =================================================================

echo "\n";
echo "┌──────────────────────────────────────────────────────────────┐\n";
echo "│ TEST SUITE 4: Circuit Breaker Functionality                 │\n";
echo "└──────────────────────────────────────────────────────────────┘\n";

runTest('Circuit breaker initial state is CLOSED', function() {
    $cb = app(\App\Domain\Shared\Services\CircuitBreakerService::class);
    $state = $cb->getState('test_service');
    return $state === 'closed';
}, $results);

runTest('Circuit breaker executes successful calls', function() {
    $cb = app(\App\Domain\Shared\Services\CircuitBreakerService::class);
    
    $result = $cb->call('test_success', function() {
        return 'success';
    });
    
    return $result === 'success';
}, $results);

runTest('Circuit breaker records failures', function() {
    $cb = app(\App\Domain\Shared\Services\CircuitBreakerService::class);
    
    try {
        $cb->call('test_failure', function() {
            throw new \Exception('Test failure');
        });
    } catch (\Exception $e) {
        // Expected
    }
    
    $stats = $cb->getStats('test_failure');
    return $stats['failures'] >= 1;
}, $results);

runTest('Circuit breaker has getStats method', function() {
    $cb = app(\App\Domain\Shared\Services\CircuitBreakerService::class);
    return method_exists($cb, 'getStats');
}, $results);

runTest('Circuit breaker stats have required fields', function() {
    $cb = app(\App\Domain\Shared\Services\CircuitBreakerService::class);
    $stats = $cb->getStats('test_service');
    
    $required = ['service', 'state', 'failures', 'successes', 'failure_threshold'];
    
    foreach ($required as $field) {
        if (!isset($stats[$field])) {
            return "Missing field: $field";
        }
    }
    
    return true;
}, $results);

runTest('Circuit breaker fallback works', function() {
    $cb = app(\App\Domain\Shared\Services\CircuitBreakerService::class);
    
    $result = $cb->call('test_fallback', 
        function() {
            throw new \Exception('Primary fails');
        },
        function() {
            return 'fallback_data';
        }
    );
    
    return $result === 'fallback_data';
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
echo "Skipped:        " . $results['skipped'] . ($results['skipped'] > 0 ? " ⚠" : "") . "\n";
echo "Pass Rate:      " . $passRate . "%\n";
echo "\n";

// Status
if ($results['failed'] === 0 && $results['skipped'] === 0) {
    echo "Status: ✅ ALL TESTS PASSED - ENDPOINTS READY!\n";
} elseif ($results['failed'] === 0) {
    echo "Status: ⚠️  TESTS PASSED WITH WARNINGS\n";
    echo "Note: Some tests were skipped (likely due to no test data)\n";
} else {
    echo "Status: ❌ TESTS FAILED - REVIEW ISSUES BELOW\n";
    echo "\n";
    echo "Failed Tests:\n";
    foreach ($results['tests'] as $name => $result) {
        if (str_starts_with($result, 'FAIL') || str_starts_with($result, 'ERROR')) {
            echo "  • $name: $result\n";
        }
    }
}

echo "\n";

// Save report
$reportFile = __DIR__ . '/storage/logs/phase1_test_report_' . date('Y-m-d_H-i-s') . '.txt';
$report = "PHASE 1 ENDPOINT TEST REPORT\n";
$report .= "Generated: " . date('Y-m-d H:i:s') . "\n";
$report .= str_repeat("=", 70) . "\n\n";
$report .= "SUMMARY\n";
$report .= "-------\n";
$report .= "Total Tests: {$results['total']}\n";
$report .= "Passed: {$results['passed']}\n";
$report .= "Failed: {$results['failed']}\n";
$report .= "Skipped: {$results['skipped']}\n";
$report .= "Pass Rate: {$passRate}%\n\n";
$report .= str_repeat("=", 70) . "\n\n";
$report .= "DETAILED RESULTS\n";
$report .= "----------------\n";

foreach ($results['tests'] as $name => $result) {
    $report .= "\n[$result] $name\n";
}

file_put_contents($reportFile, $report);
echo "Detailed report saved to: $reportFile\n";
echo "\n";

// Exit with appropriate code
exit($results['failed'] > 0 ? 1 : 0);
