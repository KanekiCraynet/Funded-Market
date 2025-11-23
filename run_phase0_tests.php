#!/usr/bin/env php
<?php

/**
 * Phase 0 Automated Test Runner
 * 
 * This script runs all Phase 0 improvement tests automatically
 * and generates a detailed report.
 */

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║         PHASE 0 IMPROVEMENTS - AUTOMATED TEST SUITE          ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Bootstrap Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Test Results Tracking
$results = [
    'total' => 0,
    'passed' => 0,
    'failed' => 0,
    'warnings' => 0,
    'tests' => []
];

/**
 * Run a test and record result
 */
function runTest(string $name, callable $test, array &$results): void
{
    $results['total']++;
    echo str_pad("• Testing: $name", 70, '.');
    
    try {
        $result = $test();
        
        if ($result === true) {
            echo " ✓ PASS\n";
            $results['passed']++;
            $results['tests'][$name] = 'PASS';
        } elseif ($result === null) {
            echo " ⚠ SKIP\n";
            $results['warnings']++;
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
// TEST SUITE 1: CONFIGURATION TESTS
// =================================================================

echo "\n";
echo "┌──────────────────────────────────────────────────────────────┐\n";
echo "│ TEST SUITE 1: Configuration Tests                           │\n";
echo "└──────────────────────────────────────────────────────────────┘\n";

runTest('Cache driver is Redis', function() {
    return config('cache.default') === 'redis';
}, $results);

runTest('Queue connection is Redis', function() {
    return config('queue.default') === 'redis';
}, $results);

runTest('Cache prefix is standardized', function() {
    $prefix = config('cache.prefix');
    return str_contains($prefix, 'market_analysis');
}, $results);

runTest('Redis connection works', function() {
    try {
        Cache::driver('redis')->getStore()->connection()->ping();
        return true;
    } catch (\Exception $e) {
        return "Redis connection failed: " . $e->getMessage();
    }
}, $results);

runTest('Cache operations work', function() {
    $key = 'test_' . time();
    $value = 'test_value_' . rand(1000, 9999);
    
    Cache::put($key, $value, 60);
    $retrieved = Cache::get($key);
    Cache::forget($key);
    
    return $retrieved === $value;
}, $results);

// =================================================================
// TEST SUITE 2: HELPER FUNCTIONS TESTS
// =================================================================

echo "\n";
echo "┌──────────────────────────────────────────────────────────────┐\n";
echo "│ TEST SUITE 2: Helper Functions Tests                        │\n";
echo "└──────────────────────────────────────────────────────────────┘\n";

runTest('format_number() exists and works', function() {
    if (!function_exists('format_number')) {
        return "Function does not exist";
    }
    $result = format_number(1234.5678, 2);
    return $result === '1,234.57';
}, $results);

runTest('format_percent() exists and works', function() {
    if (!function_exists('format_percent')) {
        return "Function does not exist";
    }
    $result = format_percent(15.5);
    return $result === '15.50%';
}, $results);

runTest('format_large_number() exists and works', function() {
    if (!function_exists('format_large_number')) {
        return "Function does not exist";
    }
    $result = format_large_number(1500000);
    return str_contains($result, '1.5') && str_contains($result, 'M');
}, $results);

runTest('calculate_percentage_change() works', function() {
    if (!function_exists('calculate_percentage_change')) {
        return "Function does not exist";
    }
    $result = calculate_percentage_change(100, 115);
    return abs($result - 15.0) < 0.01;
}, $results);

runTest('safe_division() prevents divide by zero', function() {
    if (!function_exists('safe_division')) {
        return "Function does not exist";
    }
    $result = safe_division(10, 0, 999);
    return $result === 999.0;
}, $results);

runTest('tanh() normalization works', function() {
    if (!function_exists('tanh')) {
        return "Function does not exist";
    }
    $result = tanh(0);
    return abs($result - 0.0) < 0.01;
}, $results);

runTest('clamp() limits values correctly', function() {
    if (!function_exists('clamp')) {
        return "Function does not exist";
    }
    $over = clamp(150, 0, 100);
    $under = clamp(-10, 0, 100);
    $within = clamp(50, 0, 100);
    // Use loose comparison for numeric values (allow int/float)
    return $over == 100 && $under == 0 && $within == 50;
}, $results);

// =================================================================
// TEST SUITE 3: INSTRUMENTSERVICE TESTS
// =================================================================

echo "\n";
echo "┌──────────────────────────────────────────────────────────────┐\n";
echo "│ TEST SUITE 3: InstrumentService Tests                       │\n";
echo "└──────────────────────────────────────────────────────────────┘\n";

runTest('InstrumentService is registered', function() {
    try {
        $service = app(\App\Domain\Market\Services\InstrumentService::class);
        return $service !== null;
    } catch (\Exception $e) {
        return "Service not registered: " . $e->getMessage();
    }
}, $results);

runTest('InstrumentService behaves as singleton (shared state)', function() {
    // Functional test: Verify that the service maintains shared state
    // which is the practical benefit of singletons
    $service1 = app(\App\Domain\Market\Services\InstrumentService::class);
    $service2 = app(\App\Domain\Market\Services\InstrumentService::class);
    
    // Reset stats via service1
    $service1->resetCacheStats();
    
    // Make a call via service2
    if (\App\Domain\Market\Models\Instrument::count() > 0) {
        $symbol = \App\Domain\Market\Models\Instrument::first()->symbol;
        $service2->findActiveBySymbol($symbol);
    }
    
    // Check stats via service1 - if singleton, it should see service2's action
    $stats1 = $service1->getCacheStats();
    $stats2 = $service2->getCacheStats();
    
    // Both should show the same stats (shared state = singleton behavior)
    return $stats1 === $stats2 && ($stats1['total_requests'] > 0 || \App\Domain\Market\Models\Instrument::count() === 0);
}, $results);

runTest('InstrumentService has getCacheStats method', function() {
    $service = app(\App\Domain\Market\Services\InstrumentService::class);
    return method_exists($service, 'getCacheStats');
}, $results);

runTest('InstrumentService can get cache stats', function() {
    $service = app(\App\Domain\Market\Services\InstrumentService::class);
    $stats = $service->getCacheStats();
    
    return isset($stats['cache_hits']) && 
           isset($stats['cache_misses']) && 
           isset($stats['hit_rate']);
}, $results);

runTest('InstrumentService findActiveBySymbol works', function() {
    $service = app(\App\Domain\Market\Services\InstrumentService::class);
    
    // Check if we have any instruments
    $count = \App\Domain\Market\Models\Instrument::count();
    if ($count === 0) {
        return null; // Skip if no data
    }
    
    $symbol = \App\Domain\Market\Models\Instrument::first()->symbol;
    $instrument = $service->findActiveBySymbol($symbol);
    
    return $instrument !== null;
}, $results);

runTest('Cache warming method exists', function() {
    $service = app(\App\Domain\Market\Services\InstrumentService::class);
    return method_exists($service, 'warmCache');
}, $results);

runTest('Cache invalidation method exists', function() {
    $service = app(\App\Domain\Market\Services\InstrumentService::class);
    return method_exists($service, 'invalidateCache');
}, $results);

// =================================================================
// TEST SUITE 4: PERFORMANCE TESTS
// =================================================================

echo "\n";
echo "┌──────────────────────────────────────────────────────────────┐\n";
echo "│ TEST SUITE 4: Performance Tests                             │\n";
echo "└──────────────────────────────────────────────────────────────┘\n";

runTest('Cache hit is faster than cache miss', function() {
    $service = app(\App\Domain\Market\Services\InstrumentService::class);
    
    // Check if we have instruments
    $instrument = \App\Domain\Market\Models\Instrument::first();
    if (!$instrument) {
        return null; // Skip if no data
    }
    
    $symbol = $instrument->symbol;
    
    // Clear cache
    Cache::forget('instrument:' . strtoupper($symbol));
    
    // Measure cache miss
    $start = microtime(true);
    $service->findActiveBySymbol($symbol);
    $missTime = (microtime(true) - $start) * 1000;
    
    // Measure cache hit
    $start = microtime(true);
    $service->findActiveBySymbol($symbol);
    $hitTime = (microtime(true) - $start) * 1000;
    
    echo " (MISS: " . round($missTime, 2) . "ms, HIT: " . round($hitTime, 2) . "ms)";
    
    return $hitTime < $missTime;
}, $results);

runTest('Warm cache response time < 20ms', function() {
    $service = app(\App\Domain\Market\Services\InstrumentService::class);
    
    $instrument = \App\Domain\Market\Models\Instrument::first();
    if (!$instrument) {
        return null;
    }
    
    // Ensure cached
    $service->findActiveBySymbol($instrument->symbol);
    
    // Measure (average of 3 calls for more accurate result)
    $times = [];
    for ($i = 0; $i < 3; $i++) {
        $start = microtime(true);
        $service->findActiveBySymbol($instrument->symbol);
        $times[] = (microtime(true) - $start) * 1000;
    }
    $avgTime = array_sum($times) / count($times);
    
    echo " (" . round($avgTime, 2) . "ms avg)";
    
    // 20ms is reasonable for cached operations with monitoring overhead
    return $avgTime < 20;
}, $results);

// =================================================================
// TEST SUITE 5: MODEL INTEGRATION TESTS
// =================================================================

echo "\n";
echo "┌──────────────────────────────────────────────────────────────┐\n";
echo "│ TEST SUITE 5: Model Integration Tests                       │\n";
echo "└──────────────────────────────────────────────────────────────┘\n";

runTest('Instrument model has invalidateCache method', function() {
    return method_exists(\App\Domain\Market\Models\Instrument::class, 'invalidateCache');
}, $results);

runTest('AppServiceProvider has checkRedisHealth method', function() {
    $reflection = new \ReflectionClass(\App\Providers\AppServiceProvider::class);
    return $reflection->hasMethod('checkRedisHealth');
}, $results);

runTest('HorizonServiceProvider exists', function() {
    return class_exists(\App\Providers\HorizonServiceProvider::class);
}, $results);

// =================================================================
// TEST SUITE 6: SCHEDULER TESTS
// =================================================================

echo "\n";
echo "┌──────────────────────────────────────────────────────────────┐\n";
echo "│ TEST SUITE 6: Scheduler Tests                               │\n";
echo "└──────────────────────────────────────────────────────────────┘\n";

runTest('Cache warming job is registered', function() {
    // Check if routes/console.php mentions warmCache
    $consoleFile = file_get_contents(__DIR__ . '/routes/console.php');
    return str_contains($consoleFile, 'warmCache');
}, $results);

runTest('Scheduler configuration is valid', function() {
    $consoleFile = file_get_contents(__DIR__ . '/routes/console.php');
    return str_contains($consoleFile, 'Schedule::call') && 
           str_contains($consoleFile, 'everyFiveMinutes');
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
echo "Skipped:        " . $results['warnings'] . ($results['warnings'] > 0 ? " ⚠" : "") . "\n";
echo "Pass Rate:      " . $passRate . "%\n";
echo "\n";

// Status indicator
if ($results['failed'] === 0 && $results['warnings'] === 0) {
    echo "Status: ✅ ALL TESTS PASSED - PRODUCTION READY!\n";
} elseif ($results['failed'] === 0 && $results['warnings'] > 0) {
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
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║                     DETAILED TEST LOG                         ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Save detailed results to file
$reportFile = __DIR__ . '/storage/logs/phase0_test_report_' . date('Y-m-d_H-i-s') . '.txt';
$report = "PHASE 0 TEST REPORT\n";
$report .= "Generated: " . date('Y-m-d H:i:s') . "\n";
$report .= str_repeat("=", 70) . "\n\n";
$report .= "SUMMARY\n";
$report .= "-------\n";
$report .= "Total Tests: {$results['total']}\n";
$report .= "Passed: {$results['passed']}\n";
$report .= "Failed: {$results['failed']}\n";
$report .= "Skipped: {$results['warnings']}\n";
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
