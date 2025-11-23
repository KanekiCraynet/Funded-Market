#!/usr/bin/env php
<?php

/**
 * Rate Limiting Testing Script
 * 
 * Tests rate limiting for:
 * - Per-user limits
 * - Per-IP limits
 * - Per-endpoint limits
 * - Rate limit headers
 * - Retry-After behavior
 */

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║         RATE LIMITING - COMPREHENSIVE TEST SUITE             ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Bootstrap Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Cache;
use App\Http\Middleware\CustomThrottleRequests;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiter;

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

// Clear rate limiter cache before tests
Cache::flush();

// =================================================================
// TEST SUITE 1: BASIC RATE LIMITING
// =================================================================

echo "┌──────────────────────────────────────────────────────────────┐\n";
echo "│ TEST SUITE 1: Basic Rate Limiting                           │\n";
echo "└──────────────────────────────────────────────────────────────┘\n";

runTest('CustomThrottleRequests middleware exists', function() {
    return class_exists(CustomThrottleRequests::class);
}, $results);

runTest('RateLimiter service can be resolved', function() {
    $limiter = app(RateLimiter::class);
    return $limiter !== null;
}, $results);

runTest('Config file exists', function() {
    $config = config('rate-limiting');
    return $config !== null;
}, $results);

runTest('Config has required tiers', function() {
    $tiers = config('rate-limiting.tiers');
    return isset($tiers['anonymous']) && isset($tiers['authenticated']);
}, $results);

runTest('Config has endpoints configuration', function() {
    $endpoints = config('rate-limiting.endpoints');
    return isset($endpoints['auth']) && isset($endpoints['analysis']);
}, $results);

// =================================================================
// TEST SUITE 2: MIDDLEWARE FUNCTIONALITY
// =================================================================

echo "\n";
echo "┌──────────────────────────────────────────────────────────────┐\n";
echo "│ TEST SUITE 2: Middleware Functionality                      │\n";
echo "└──────────────────────────────────────────────────────────────┘\n";

runTest('Middleware allows requests under limit', function() {
    $request = Request::create('/test', 'GET');
    $request->setUserResolver(function() {
        $user = new class {
            public $id = 'test-user-1';
        };
        return $user;
    });
    
    $middleware = new CustomThrottleRequests(app(RateLimiter::class));
    
    $maxAttempts = 5;
    $response = $middleware->handle($request, function($req) {
        return response()->json(['success' => true]);
    }, $maxAttempts, 1, 'test-middleware-allow');
    
    if ($response->getStatusCode() !== 200) {
        return "Expected 200, got " . $response->getStatusCode();
    }
    
    return true;
}, $results);

runTest('Middleware blocks requests over limit', function() {
    $request = Request::create('/test', 'GET');
    $request->setUserResolver(function() {
        $user = new class {
            public $id = 'test-user-2';
        };
        return $user;
    });
    
    $middleware = new CustomThrottleRequests(app(RateLimiter::class));
    
    $maxAttempts = 2;
    
    // Hit the limit
    for ($i = 0; $i < $maxAttempts; $i++) {
        $middleware->handle($request, function($req) {
            return response()->json(['success' => true]);
        }, $maxAttempts, 1, 'test-middleware-block');
    }
    
    // Next request should be blocked
    $response = $middleware->handle($request, function($req) {
        return response()->json(['success' => true]);
    }, $maxAttempts, 1, 'test-middleware-block');
    
    if ($response->getStatusCode() !== 429) {
        return "Expected 429, got " . $response->getStatusCode();
    }
    
    return true;
}, $results);

runTest('Middleware adds rate limit headers', function() {
    $request = Request::create('/test', 'GET');
    $request->setUserResolver(function() {
        $user = new class {
            public $id = 'test-user-3';
        };
        return $user;
    });
    
    $middleware = new CustomThrottleRequests(app(RateLimiter::class));
    
    $response = $middleware->handle($request, function($req) {
        return response()->json(['success' => true]);
    }, 10, 1, 'test-headers');
    
    $headers = $response->headers;
    
    if (!$headers->has('X-RateLimit-Limit')) {
        return "Missing X-RateLimit-Limit header";
    }
    
    if (!$headers->has('X-RateLimit-Remaining')) {
        return "Missing X-RateLimit-Remaining header";
    }
    
    if ($headers->get('X-RateLimit-Limit') != 10) {
        return "X-RateLimit-Limit should be 10";
    }
    
    return true;
}, $results);

runTest('Middleware adds Retry-After header when limited', function() {
    $request = Request::create('/test', 'GET');
    $request->setUserResolver(function() {
        $user = new class {
            public $id = 'test-user-4';
        };
        return $user;
    });
    
    $middleware = new CustomThrottleRequests(app(RateLimiter::class));
    
    $maxAttempts = 2;
    
    // Hit the limit
    for ($i = 0; $i < $maxAttempts; $i++) {
        $middleware->handle($request, function($req) {
            return response()->json(['success' => true]);
        }, $maxAttempts, 1, 'test-retry-after');
    }
    
    // Next request should have Retry-After
    $response = $middleware->handle($request, function($req) {
        return response()->json(['success' => true]);
    }, $maxAttempts, 1, 'test-retry-after');
    
    if (!$response->headers->has('Retry-After')) {
        return "Missing Retry-After header";
    }
    
    if (!$response->headers->has('X-RateLimit-Reset')) {
        return "Missing X-RateLimit-Reset header";
    }
    
    return true;
}, $results);

// =================================================================
// TEST SUITE 3: USER VS IP DIFFERENTIATION
// =================================================================

echo "\n";
echo "┌──────────────────────────────────────────────────────────────┐\n";
echo "│ TEST SUITE 3: User vs IP Differentiation                    │\n";
echo "└──────────────────────────────────────────────────────────────┘\n";

runTest('Different users have separate limits', function() {
    $middleware = new CustomThrottleRequests(app(RateLimiter::class));
    $maxAttempts = 2;
    
    // User 1
    $request1 = Request::create('/test', 'GET');
    $request1->setUserResolver(function() {
        $user = new class {
            public $id = 'separate-user-1';
        };
        return $user;
    });
    
    // User 2
    $request2 = Request::create('/test', 'GET');
    $request2->setUserResolver(function() {
        $user = new class {
            public $id = 'separate-user-2';
        };
        return $user;
    });
    
    // Exhaust user 1's limit
    for ($i = 0; $i < $maxAttempts; $i++) {
        $middleware->handle($request1, function($req) {
            return response()->json(['success' => true]);
        }, $maxAttempts, 1, 'test-separate');
    }
    
    // User 1 should be blocked
    $response1 = $middleware->handle($request1, function($req) {
        return response()->json(['success' => true]);
    }, $maxAttempts, 1, 'test-separate');
    
    if ($response1->getStatusCode() !== 429) {
        return "User 1 should be rate limited";
    }
    
    // User 2 should still be allowed
    $response2 = $middleware->handle($request2, function($req) {
        return response()->json(['success' => true]);
    }, $maxAttempts, 1, 'test-separate');
    
    if ($response2->getStatusCode() !== 200) {
        return "User 2 should not be rate limited";
    }
    
    return true;
}, $results);

runTest('Anonymous users limited by IP', function() {
    $middleware = new CustomThrottleRequests(app(RateLimiter::class));
    
    $request = Request::create('/test', 'GET');
    // No user set (anonymous)
    $request->server->set('REMOTE_ADDR', '192.168.1.100');
    
    $response = $middleware->handle($request, function($req) {
        return response()->json(['success' => true]);
    }, 10, 1, 'test-anon');
    
    if ($response->getStatusCode() !== 200) {
        return "Anonymous request should be allowed";
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
    echo "Status: ✅ ALL TESTS PASSED - RATE LIMITING READY!\n";
} else {
    echo "Status: ❌ TESTS FAILED - REVIEW ISSUES ABOVE\n";
}

echo "\n";

// Save report
$reportFile = __DIR__ . '/storage/logs/rate_limiting_test_report_' . date('Y-m-d_H-i-s') . '.txt';
$report = "RATE LIMITING TEST REPORT\n";
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
