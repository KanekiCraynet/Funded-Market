#!/usr/bin/env php
<?php

/**
 * API Response Caching Testing Script
 * 
 * Tests the API response caching middleware to verify:
 * - Cache MISS and HIT behavior
 * - TTL configuration
 * - Bypass mechanisms
 * - Cache headers
 * - Performance metrics
 */

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║        API RESPONSE CACHING - TEST SUITE                     ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Bootstrap Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use App\Http\Middleware\CacheApiResponse;

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

// Clear cache before tests
Cache::flush();

// =================================================================
// TEST SUITE 1: CONFIGURATION
// =================================================================

echo "┌──────────────────────────────────────────────────────────────┐\n";
echo "│ TEST SUITE 1: Configuration                                  │\n";
echo "└──────────────────────────────────────────────────────────────┘\n";

runTest('API cache config file exists', function() {
    $config = config('api-cache');
    return $config !== null;
}, $results);

runTest('API cache is enabled', function() {
    $enabled = config('api-cache.enabled');
    if (!$enabled) {
        return "API cache is disabled in config";
    }
    return true;
}, $results);

runTest('TTL configuration exists', function() {
    $ttls = config('api-cache.ttl');
    if (!is_array($ttls)) {
        return "TTL configuration is not an array";
    }
    
    $required = ['market', 'analysis', 'quant', 'sentiment', 'default'];
    foreach ($required as $key) {
        if (!isset($ttls[$key])) {
            return "Missing TTL configuration for: {$key}";
        }
    }
    
    return true;
}, $results);

runTest('Endpoint patterns are configured', function() {
    $patterns = config('api-cache.patterns');
    if (!is_array($patterns)) {
        return "Patterns configuration is not an array";
    }
    
    if (empty($patterns)) {
        return "No patterns configured";
    }
    
    return true;
}, $results);

runTest('Cache middleware class exists', function() {
    return class_exists(CacheApiResponse::class);
}, $results);

// =================================================================
// TEST SUITE 2: CACHE KEY GENERATION
// =================================================================

echo "\n";
echo "┌──────────────────────────────────────────────────────────────┐\n";
echo "│ TEST SUITE 2: Cache Key Generation                          │\n";
echo "└──────────────────────────────────────────────────────────────┘\n";

runTest('Cache keys are unique per URL', function() {
    $middleware = new CacheApiResponse();
    $reflection = new ReflectionClass($middleware);
    $method = $reflection->getMethod('generateCacheKey');
    $method->setAccessible(true);
    
    $request1 = Request::create('/api/v1/market/overview', 'GET');
    $request2 = Request::create('/api/v1/market/tickers', 'GET');
    
    $key1 = $method->invoke($middleware, $request1);
    $key2 = $method->invoke($middleware, $request2);
    
    if ($key1 === $key2) {
        return "Different URLs generated same cache key";
    }
    
    return true;
}, $results);

runTest('Cache keys include query parameters', function() {
    $middleware = new CacheApiResponse();
    $reflection = new ReflectionClass($middleware);
    $method = $reflection->getMethod('generateCacheKey');
    $method->setAccessible(true);
    
    $request1 = Request::create('/api/v1/market/overview', 'GET');
    $request2 = Request::create('/api/v1/market/overview?limit=10', 'GET');
    
    $key1 = $method->invoke($middleware, $request1);
    $key2 = $method->invoke($middleware, $request2);
    
    if ($key1 === $key2) {
        return "Query parameters not included in cache key";
    }
    
    return true;
}, $results);

runTest('Cache keys have correct prefix', function() {
    $middleware = new CacheApiResponse();
    $reflection = new ReflectionClass($middleware);
    $method = $reflection->getMethod('generateCacheKey');
    $method->setAccessible(true);
    
    $request = Request::create('/api/v1/market/overview', 'GET');
    $key = $method->invoke($middleware, $request);
    
    $prefix = config('api-cache.key.prefix', 'api_response:');
    if (!str_starts_with($key, $prefix)) {
        return "Cache key doesn't have correct prefix. Got: {$key}";
    }
    
    return true;
}, $results);

// =================================================================
// TEST SUITE 3: TTL SELECTION
// =================================================================

echo "\n";
echo "┌──────────────────────────────────────────────────────────────┐\n";
echo "│ TEST SUITE 3: TTL Selection                                  │\n";
echo "└──────────────────────────────────────────────────────────────┘\n";

runTest('Market endpoints get market TTL', function() {
    $middleware = new CacheApiResponse();
    $reflection = new ReflectionClass($middleware);
    $method = $reflection->getMethod('getTTL');
    $method->setAccessible(true);
    
    $request = Request::create('/api/v1/market/overview', 'GET');
    $ttl = $method->invoke($middleware, $request);
    
    $expectedTTL = config('api-cache.ttl.market', 300);
    if ($ttl !== $expectedTTL) {
        return "Expected TTL {$expectedTTL}, got {$ttl}";
    }
    
    return true;
}, $results);

runTest('Analysis endpoints get analysis TTL', function() {
    $middleware = new CacheApiResponse();
    $reflection = new ReflectionClass($middleware);
    $method = $reflection->getMethod('getTTL');
    $method->setAccessible(true);
    
    $request = Request::create('/api/v1/analysis/generate', 'GET');
    $ttl = $method->invoke($middleware, $request);
    
    $expectedTTL = config('api-cache.ttl.analysis', 3600);
    if ($ttl !== $expectedTTL) {
        return "Expected TTL {$expectedTTL}, got {$ttl}";
    }
    
    return true;
}, $results);

runTest('Unknown endpoints get default TTL', function() {
    $middleware = new CacheApiResponse();
    $reflection = new ReflectionClass($middleware);
    $method = $reflection->getMethod('getTTL');
    $method->setAccessible(true);
    
    $request = Request::create('/api/v1/unknown/endpoint', 'GET');
    $ttl = $method->invoke($middleware, $request);
    
    $expectedTTL = config('api-cache.ttl.default', 300);
    if ($ttl !== $expectedTTL) {
        return "Expected default TTL {$expectedTTL}, got {$ttl}";
    }
    
    return true;
}, $results);

// =================================================================
// TEST SUITE 4: EXCLUSIONS
// =================================================================

echo "\n";
echo "┌──────────────────────────────────────────────────────────────┐\n";
echo "│ TEST SUITE 4: Endpoint Exclusions                           │\n";
echo "└──────────────────────────────────────────────────────────────┘\n";

runTest('Auth login endpoint is excluded', function() {
    $middleware = new CacheApiResponse();
    $reflection = new ReflectionClass($middleware);
    $method = $reflection->getMethod('isExcluded');
    $method->setAccessible(true);
    
    $request = Request::create('/api/v1/auth/login', 'GET');
    $excluded = $method->invoke($middleware, $request);
    
    if (!$excluded) {
        return "Login endpoint should be excluded from caching";
    }
    
    return true;
}, $results);

runTest('Auth register endpoint is excluded', function() {
    $middleware = new CacheApiResponse();
    $reflection = new ReflectionClass($middleware);
    $method = $reflection->getMethod('isExcluded');
    $method->setAccessible(true);
    
    $request = Request::create('/api/v1/auth/register', 'GET');
    $excluded = $method->invoke($middleware, $request);
    
    if (!$excluded) {
        return "Register endpoint should be excluded from caching";
    }
    
    return true;
}, $results);

runTest('Market endpoints are not excluded', function() {
    $middleware = new CacheApiResponse();
    $reflection = new ReflectionClass($middleware);
    $method = $reflection->getMethod('isExcluded');
    $method->setAccessible(true);
    
    $request = Request::create('/api/v1/market/overview', 'GET');
    $excluded = $method->invoke($middleware, $request);
    
    if ($excluded) {
        return "Market endpoint should not be excluded";
    }
    
    return true;
}, $results);

// =================================================================
// TEST SUITE 5: BYPASS MECHANISMS
// =================================================================

echo "\n";
echo "┌──────────────────────────────────────────────────────────────┐\n";
echo "│ TEST SUITE 5: Bypass Mechanisms                             │\n";
echo "└──────────────────────────────────────────────────────────────┘\n";

runTest('no-cache query parameter triggers bypass', function() {
    $middleware = new CacheApiResponse();
    $reflection = new ReflectionClass($middleware);
    $method = $reflection->getMethod('shouldBypass');
    $method->setAccessible(true);
    
    $request = Request::create('/api/v1/market/overview?no-cache=1', 'GET');
    $bypass = $method->invoke($middleware, $request);
    
    if (!$bypass) {
        return "no-cache parameter should trigger bypass";
    }
    
    return true;
}, $results);

runTest('refresh query parameter triggers bypass', function() {
    $middleware = new CacheApiResponse();
    $reflection = new ReflectionClass($middleware);
    $method = $reflection->getMethod('shouldBypass');
    $method->setAccessible(true);
    
    $request = Request::create('/api/v1/market/overview?refresh=1', 'GET');
    $bypass = $method->invoke($middleware, $request);
    
    if (!$bypass) {
        return "refresh parameter should trigger bypass";
    }
    
    return true;
}, $results);

runTest('Cache-Control: no-cache header triggers bypass', function() {
    $middleware = new CacheApiResponse();
    $reflection = new ReflectionClass($middleware);
    $method = $reflection->getMethod('shouldBypass');
    $method->setAccessible(true);
    
    $request = Request::create('/api/v1/market/overview', 'GET');
    $request->headers->set('Cache-Control', 'no-cache');
    $bypass = $method->invoke($middleware, $request);
    
    if (!$bypass) {
        return "Cache-Control: no-cache header should trigger bypass";
    }
    
    return true;
}, $results);

// =================================================================
// TEST SUITE 6: REQUEST METHOD FILTERING
// =================================================================

echo "\n";
echo "┌──────────────────────────────────────────────────────────────┐\n";
echo "│ TEST SUITE 6: Request Method Filtering                      │\n";
echo "└──────────────────────────────────────────────────────────────┘\n";

runTest('GET requests are cached', function() {
    $middleware = new CacheApiResponse();
    
    $request = Request::create('/api/v1/market/overview', 'GET');
    
    // Simulate the middleware handling
    $response = $middleware->handle($request, function($req) {
        return response()->json(['data' => 'test'], 200);
    });
    
    // Should process successfully (no exception)
    return $response->getStatusCode() === 200;
}, $results);

runTest('POST requests bypass cache', function() {
    $middleware = new CacheApiResponse();
    
    $request = Request::create('/api/v1/analysis/generate', 'POST');
    
    $wasCalled = false;
    $response = $middleware->handle($request, function($req) use (&$wasCalled) {
        $wasCalled = true;
        return response()->json(['data' => 'test'], 200);
    });
    
    if (!$wasCalled) {
        return "POST request handler was not called (cache incorrectly applied)";
    }
    
    return true;
}, $results);

runTest('PUT requests bypass cache', function() {
    $middleware = new CacheApiResponse();
    
    $request = Request::create('/api/v1/analysis/1', 'PUT');
    
    $wasCalled = false;
    $response = $middleware->handle($request, function($req) use (&$wasCalled) {
        $wasCalled = true;
        return response()->json(['data' => 'test'], 200);
    });
    
    if (!$wasCalled) {
        return "PUT request handler was not called (cache incorrectly applied)";
    }
    
    return true;
}, $results);

runTest('DELETE requests bypass cache', function() {
    $middleware = new CacheApiResponse();
    
    $request = Request::create('/api/v1/analysis/1', 'DELETE');
    
    $wasCalled = false;
    $response = $middleware->handle($request, function($req) use (&$wasCalled) {
        $wasCalled = true;
        return response()->json(['data' => 'test'], 200);
    });
    
    if (!$wasCalled) {
        return "DELETE request handler was not called (cache incorrectly applied)";
    }
    
    return true;
}, $results);

// =================================================================
// TEST SUITE 7: CACHE MISS AND HIT
// =================================================================

echo "\n";
echo "┌──────────────────────────────────────────────────────────────┐\n";
echo "│ TEST SUITE 7: Cache MISS and HIT Behavior                   │\n";
echo "└──────────────────────────────────────────────────────────────┘\n";

runTest('First request is cache MISS', function() {
    Cache::flush();
    
    $middleware = new CacheApiResponse();
    $request = Request::create('/api/v1/test/cache-miss', 'GET');
    
    $handlerCalled = false;
    $response = $middleware->handle($request, function($req) use (&$handlerCalled) {
        $handlerCalled = true;
        return response()->json(['data' => 'original'], 200);
    });
    
    if (!$handlerCalled) {
        return "Handler not called on cache MISS";
    }
    
    return true;
}, $results);

runTest('Second request is cache HIT', function() {
    $middleware = new CacheApiResponse();
    $request = Request::create('/api/v1/test/cache-miss', 'GET');
    
    // First request to populate cache
    $middleware->handle($request, function($req) {
        return response()->json(['data' => 'original'], 200);
    });
    
    // Second request should hit cache
    $handlerCalled = false;
    $response = $middleware->handle($request, function($req) use (&$handlerCalled) {
        $handlerCalled = true;
        return response()->json(['data' => 'should not see this'], 200);
    });
    
    if ($handlerCalled) {
        return "Handler was called on cache HIT (should use cached response)";
    }
    
    // Check response has cache headers
    $cacheHeader = $response->headers->get('X-Cache');
    if ($cacheHeader !== 'HIT') {
        return "Expected X-Cache: HIT header, got: " . ($cacheHeader ?? 'null');
    }
    
    return true;
}, $results);

runTest('Cached response content is correct', function() {
    Cache::flush();
    
    $middleware = new CacheApiResponse();
    $request = Request::create('/api/v1/test/content', 'GET');
    
    // First request
    $response1 = $middleware->handle($request, function($req) {
        return response()->json(['data' => 'test-value', 'timestamp' => time()], 200);
    });
    
    $content1 = json_decode($response1->getContent(), true);
    
    // Wait a tiny bit
    usleep(10000);
    
    // Second request (should be cached)
    $response2 = $middleware->handle($request, function($req) {
        return response()->json(['data' => 'different-value', 'timestamp' => time()], 200);
    });
    
    $content2 = json_decode($response2->getContent(), true);
    
    // Content should be identical (from cache)
    if ($content1 !== $content2) {
        return "Cached content differs from original";
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
    echo "Status: ✅ ALL TESTS PASSED - API CACHING WORKS!\n";
    echo "\n";
    echo "Summary:\n";
    echo "- ✅ Configuration is correct\n";
    echo "- ✅ Cache keys are unique and include query params\n";
    echo "- ✅ TTL selection works for different endpoints\n";
    echo "- ✅ Auth endpoints are properly excluded\n";
    echo "- ✅ Bypass mechanisms work (no-cache, refresh)\n";
    echo "- ✅ Only GET requests are cached\n";
    echo "- ✅ Cache MISS and HIT behavior is correct\n";
    echo "- ✅ Cached content is properly stored and retrieved\n";
} else {
    echo "Status: ❌ TESTS FAILED - REVIEW ISSUES ABOVE\n";
}

echo "\n";

// Save report
$reportFile = __DIR__ . '/storage/logs/api_cache_test_report_' . date('Y-m-d_H-i-s') . '.txt';
$report = "API RESPONSE CACHING TEST REPORT\n";
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
