# PHASE 0 - COMPREHENSIVE TESTING SUITE

**Purpose**: Verify all improvements are working correctly  
**Duration**: ~15-20 minutes  
**Requirements**: Redis running, Laravel app configured

---

## 🎯 TESTING CHECKLIST

- [ ] Configuration Tests (5 tests)
- [ ] Helper Functions Tests (14 tests)
- [ ] InstrumentService Tests (8 tests)
- [ ] Cache Invalidation Tests (3 tests)
- [ ] Cache Warming Tests (3 tests)
- [ ] Redis Health Check Tests (3 tests)
- [ ] Monitoring Tests (3 tests)
- [ ] Integration Tests (5 tests)

**Total**: 44 tests

---

## 📋 PRE-TEST SETUP

```bash
# 1. Ensure Redis is running
redis-cli ping
# Expected: PONG

# 2. Clear all caches
php artisan optimize:clear

# 3. Start fresh
php artisan config:clear

# 4. Check app is working
php artisan about
```

---

## TEST SUITE 1: Configuration Tests

### Test 1.1: Cache Driver Configuration
```bash
php artisan tinker
```
```php
// Test 1: Check cache driver
config('cache.default')
// Expected: "redis"

// Test 2: Verify Redis connection
Cache::driver('redis')->getStore()->connection()->ping();
// Expected: "+PONG"

// Test 3: Check cache prefix
config('cache.prefix')
// Expected: "market_analysis_cache_"

// Test 4: Test cache operations
Cache::put('test_key', 'test_value', 60);
Cache::get('test_key');
// Expected: "test_value"

// Test 5: Verify queue configuration
config('queue.default')
// Expected: "redis"

exit
```

**✅ PASS CRITERIA**: All commands return expected values

---

## TEST SUITE 2: Helper Functions Tests

```bash
php artisan tinker
```

### Test 2.1: Number Formatting Functions
```php
// Test 1: format_number
format_number(1234.5678, 2)
// Expected: "1,234.57"

// Test 2: format_percent
format_percent(15.5)
// Expected: "15.50%"

// Test 3: format_currency
format_currency(1234.56, 'USD')
// Expected: "$1,234.56"

// Test 4: format_large_number
format_large_number(1500000)
// Expected: "1.50M"

format_large_number(2500000000)
// Expected: "2.50B"

format_large_number(1500000000000)
// Expected: "1.50T"
```

### Test 2.2: Calculation Functions
```php
// Test 5: calculate_percentage_change
calculate_percentage_change(100, 115)
// Expected: 15.0

calculate_percentage_change(100, 85)
// Expected: -15.0

// Test 6: safe_division
safe_division(10, 2)
// Expected: 5.0

safe_division(10, 0, 999)
// Expected: 999 (fallback value)

// Test 7: calculate_volatility
calculate_volatility([100, 102, 98, 101, 99])
// Expected: ~1.58 (standard deviation)

// Test 8: calculate_sharpe_ratio
calculate_sharpe_ratio(0.15, 0.02, 0.05)
// Expected: 2.6
```

### Test 2.3: Normalization Functions
```php
// Test 9: tanh
tanh(0)
// Expected: 0.0

tanh(0.5)
// Expected: ~0.46

tanh(1000)
// Expected: ~1.0 (max)

// Test 10: clamp
clamp(150, 0, 100)
// Expected: 100

clamp(-10, 0, 100)
// Expected: 0

clamp(50, 0, 100)
// Expected: 50

// Test 11: normalize_score
normalize_score(5, 0, 10)
// Expected: 0.0 (middle = 0 in [-1, 1] range)

normalize_score(10, 0, 10)
// Expected: 1.0

normalize_score(0, 0, 10)
// Expected: -1.0
```

### Test 2.4: Utility Functions
```php
// Test 12: market_status
market_status()
// Expected: "open" or "closed" (depends on current time)

// Test 13: is_market_open
is_market_open()
// Expected: true or false

// Test 14: round_to_significant
round_to_significant(1234.5678, 3)
// Expected: 1230.0

round_to_significant(0.001234, 2)
// Expected: 0.0012

exit
```

**✅ PASS CRITERIA**: All functions return correct values

---

## TEST SUITE 3: InstrumentService Tests

```bash
php artisan tinker
```

### Test 3.1: Basic Service Operations
```php
// Get the service
$service = app(\App\Domain\Market\Services\InstrumentService::class);

// Test 1: Service is singleton
$service2 = app(\App\Domain\Market\Services\InstrumentService::class);
$service === $service2
// Expected: true (same instance)

// Test 2: Get cache stats (initial state)
$service->getCacheStats()
// Expected: Array with hits, misses, hit_rate

// Test 3: Check if we have any instruments
\App\Domain\Market\Models\Instrument::count()
// Expected: > 0 (if database is seeded)
```

### Test 3.2: Cache Performance Test
```php
// Test 4: First lookup (cache MISS)
$start = microtime(true);
$instrument = $service->findActiveBySymbol('BTCUSDT');
$firstCallTime = (microtime(true) - $start) * 1000;
echo "First call (MISS): {$firstCallTime}ms\n";
// Expected: 50-100ms (database query)

// Test 5: Second lookup (cache HIT)
$start = microtime(true);
$instrument = $service->findActiveBySymbol('BTCUSDT');
$secondCallTime = (microtime(true) - $start) * 1000;
echo "Second call (HIT): {$secondCallTime}ms\n";
// Expected: <2ms (from cache)

// Verify performance improvement
echo "Improvement: " . round($firstCallTime / $secondCallTime, 1) . "x faster\n";
// Expected: 50-100x faster

// Test 6: Check cache stats after lookups
$stats = $service->getCacheStats();
echo "Cache stats:\n";
print_r($stats);
// Expected: Shows hits and misses
```

### Test 3.3: Cache Key Normalization
```php
// Test 7: Symbol normalization
$btc1 = $service->findActiveBySymbol('btcusdt');
$btc2 = $service->findActiveBySymbol('BTCUSDT');
$btc3 = $service->findActiveBySymbol('BtcUsDt');

$btc1 === $btc2 && $btc2 === $btc3
// Expected: true (all return same cached object)

// Test 8: Check Redis keys
\Cache::driver('redis')->getStore()->connection()->keys('instrument:*')
// Expected: Array of keys like ["instrument:BTCUSDT"]

exit
```

**✅ PASS CRITERIA**: 
- First call takes 50-100ms
- Second call takes <2ms
- 50-100x performance improvement
- All normalizations work

---

## TEST SUITE 4: Cache Invalidation Tests

```bash
php artisan tinker
```

### Test 4.1: Automatic Invalidation on Update
```php
// Setup: Cache an instrument
$service = app(\App\Domain\Market\Services\InstrumentService::class);
$instrument = \App\Domain\Market\Models\Instrument::first();

if (!$instrument) {
    echo "No instruments found. Please seed database first.\n";
    exit;
}

// Cache the instrument
$cached1 = $service->findActiveBySymbol($instrument->symbol);
$originalPrice = $cached1->price;
echo "Original price: $originalPrice\n";

// Check it's in Redis
$inCache = \Cache::has('instrument:' . strtoupper($instrument->symbol));
echo "In cache before update: " . ($inCache ? 'YES' : 'NO') . "\n";
// Expected: YES

// Test 1: Update the instrument
$newPrice = $originalPrice + 1000;
$instrument->update(['price' => $newPrice]);
echo "Updated price to: $newPrice\n";

// Test 2: Check cache was invalidated
sleep(1); // Give it a moment
$inCache = \Cache::has('instrument:' . strtoupper($instrument->symbol));
echo "In cache after update: " . ($inCache ? 'YES' : 'NO') . "\n";
// Expected: NO (cache should be cleared)

// Test 3: Next lookup gets fresh data
$cached2 = $service->findActiveBySymbol($instrument->symbol);
echo "Price after reload: {$cached2->price}\n";
// Expected: $newPrice (new value from database)

$cached2->price == $newPrice
// Expected: true

// Restore original price
$instrument->update(['price' => $originalPrice]);

exit
```

**✅ PASS CRITERIA**: 
- Cache exists before update
- Cache cleared after update
- Fresh data loaded on next request

---

## TEST SUITE 5: Cache Warming Tests

```bash
php artisan tinker
```

### Test 5.1: Manual Cache Warming
```php
$service = app(\App\Domain\Market\Services\InstrumentService::class);

// Test 1: Get initial cache state
$initialStats = $service->getCacheStats();
echo "Initial cache stats:\n";
print_r($initialStats);

// Test 2: Warm cache with specific symbols
$symbols = ['BTCUSDT', 'ETHUSD', 'BNBUSDT'];
$service->warmCache($symbols);
echo "Cache warmed for " . count($symbols) . " symbols\n";

// Test 3: Verify all symbols are cached
foreach ($symbols as $symbol) {
    $inCache = \Cache::has('instrument:' . $symbol);
    echo "$symbol cached: " . ($inCache ? 'YES ✓' : 'NO ✗') . "\n";
}
// Expected: All YES

// Test 4: Verify next lookups are fast (cache hits)
foreach ($symbols as $symbol) {
    $start = microtime(true);
    $service->findActiveBySymbol($symbol);
    $time = (microtime(true) - $start) * 1000;
    echo "$symbol lookup: {$time}ms\n";
}
// Expected: All <2ms

exit
```

### Test 5.2: Scheduled Cache Warming
```bash
# Test the scheduled job
php artisan schedule:test

# Expected output should include:
# warm-instrument-cache .... Next Due: X minutes from now
```

### Test 5.3: Run Cache Warming Job Manually
```bash
# List scheduled tasks
php artisan schedule:list

# Run all scheduled tasks (will run warming if due)
php artisan schedule:run

# Check logs
tail -n 50 storage/logs/laravel.log | grep -i "cache warm"
# Expected: "Instrument cache warmed successfully"
```

**✅ PASS CRITERIA**: 
- Warming completes successfully
- All symbols cached
- Subsequent lookups are fast (<2ms)
- Scheduled job is registered

---

## TEST SUITE 6: Redis Health Check Tests

### Test 6.1: Health Check with Redis Running
```bash
# Redis should be running
redis-cli ping
# Expected: PONG

php artisan tinker
```
```php
// Check cache driver
config('cache.default')
// Expected: "redis"

// Test cache operation
\Cache::put('health_test', 'ok', 60);
\Cache::get('health_test')
// Expected: "ok"

exit
```

### Test 6.2: Health Check with Redis Stopped
```bash
# Stop Redis
sudo systemctl stop redis
# Or: redis-cli shutdown

# Restart Laravel (to trigger health check)
php artisan config:clear

# Check what cache driver is being used
php artisan tinker
```
```php
config('cache.default')
// Expected: "file" (automatic fallback)

// Test cache still works (slower, but works)
\Cache::put('fallback_test', 'working', 60);
\Cache::get('fallback_test')
// Expected: "working"

exit
```

### Test 6.3: Restore Redis
```bash
# Start Redis again
sudo systemctl start redis
redis-cli ping
# Expected: PONG

# Clear config and restart
php artisan config:clear

# Verify back to Redis
php artisan tinker
```
```php
config('cache.default')
// Expected: "redis"

exit
```

**✅ PASS CRITERIA**: 
- App works with Redis running
- App automatically falls back to file cache when Redis down
- App returns to Redis when restored
- No application crashes

---

## TEST SUITE 7: Monitoring Tests

```bash
php artisan tinker
```

### Test 7.1: Cache Statistics Collection
```php
$service = app(\App\Domain\Market\Services\InstrumentService::class);

// Reset stats to start fresh
$service->resetCacheStats();

// Perform some lookups
for ($i = 0; $i < 10; $i++) {
    $service->findActiveBySymbol('BTCUSDT');
}

// Check stats
$stats = $service->getCacheStats();
print_r($stats);

// Verify stats
$stats['cache_hits'] >= 9  // First is miss, rest are hits
// Expected: true

$stats['cache_misses'] <= 1
// Expected: true

// Calculate expected hit rate
$expectedRate = (9 / 10) * 100;  // 90%
echo "Expected hit rate: {$expectedRate}%\n";
echo "Actual hit rate: {$stats['hit_rate']}\n";
```

### Test 7.2: Periodic Logging (100 Requests)
```php
// This will trigger automatic logging at 100 requests
for ($i = 0; $i < 100; $i++) {
    if ($i % 2 == 0) {
        $service->findActiveBySymbol('BTCUSDT');  // Cache hit
    } else {
        $service->findActiveBySymbol('ETHUSD');   // Cache hit after first
    }
}

// Check final stats
$stats = $service->getCacheStats();
print_r($stats);

exit
```

### Test 7.3: Check Logs for Statistics
```bash
# Check last 100 lines of logs for cache statistics
tail -n 100 storage/logs/laravel.log | grep -i "cache statistics"

# Expected: Should see periodic log entries like:
# "Instrument cache statistics" with hits, misses, hit_rate
```

**✅ PASS CRITERIA**: 
- Stats are tracked correctly
- Hit rate calculation is accurate
- Automatic logging happens every 100 requests
- Logs show cache performance

---

## TEST SUITE 8: Integration Tests

### Test 8.1: Complete Request Flow Test
```bash
# This tests the entire flow from request to response

# First, ensure we have test data
php artisan tinker
```
```php
// Check we have instruments
\App\Domain\Market\Models\Instrument::count()
// Expected: > 0

// Get a test symbol
$symbol = \App\Domain\Market\Models\Instrument::first()->symbol;
echo "Test symbol: $symbol\n";

exit
```

### Test 8.2: API Request Test (if API is running)
```bash
# Start the development server if not already running
php artisan serve &

# Wait a moment
sleep 2

# Make test request (assuming you have a test user/token)
# curl -X POST http://localhost:8000/api/v1/analysis/generate \
#   -H "Authorization: Bearer YOUR_TOKEN" \
#   -H "Content-Type: application/json" \
#   -d '{"symbol": "BTCUSDT"}'

# Expected: 200 OK response with analysis data
```

### Test 8.3: Controller Test
```bash
php artisan tinker
```
```php
// Simulate what controller does
$service = app(\App\Domain\Market\Services\InstrumentService::class);

// Test valid symbol
$instrument = $service->findActiveBySymbol('BTCUSDT');
if ($instrument) {
    echo "✓ Valid symbol found\n";
} else {
    echo "✗ Valid symbol NOT found\n";
}

// Test invalid symbol
$invalid = $service->findActiveBySymbol('INVALIDSYMBOL123');
if ($invalid === null) {
    echo "✓ Invalid symbol correctly returns null\n";
} else {
    echo "✗ Invalid symbol should return null\n";
}

exit
```

### Test 8.4: End-to-End Performance Test
```bash
php artisan tinker
```
```php
// Complete flow performance test
$service = app(\App\Domain\Market\Services\InstrumentService::class);
$symbol = 'BTCUSDT';

echo "=== End-to-End Performance Test ===\n\n";

// Cold cache scenario
\Cache::forget('instrument:' . $symbol);
$start = microtime(true);
$instrument = $service->findActiveBySymbol($symbol);
$coldTime = (microtime(true) - $start) * 1000;
echo "Cold cache (first request): {$coldTime}ms\n";

// Warm cache scenario
$times = [];
for ($i = 0; $i < 10; $i++) {
    $start = microtime(true);
    $service->findActiveBySymbol($symbol);
    $times[] = (microtime(true) - $start) * 1000;
}
$avgWarmTime = array_sum($times) / count($times);
echo "Warm cache (avg of 10): {$avgWarmTime}ms\n";

// Calculate improvement
$improvement = round($coldTime / $avgWarmTime, 1);
echo "\nPerformance improvement: {$improvement}x faster\n";

// Verify it meets our goals
if ($avgWarmTime < 2 && $improvement > 25) {
    echo "\n✓ PERFORMANCE TEST PASSED\n";
    echo "  - Warm cache: <2ms ✓\n";
    echo "  - Improvement: >25x ✓\n";
} else {
    echo "\n✗ PERFORMANCE TEST FAILED\n";
    echo "  - Warm cache should be <2ms\n";
    echo "  - Improvement should be >25x\n";
}

exit
```

### Test 8.5: Stress Test (Optional)
```bash
php artisan tinker
```
```php
// Stress test: 1000 requests
$service = app(\App\Domain\Market\Services\InstrumentService::class);
$service->resetCacheStats();

echo "Running stress test: 1000 requests...\n";

$start = microtime(true);
for ($i = 0; $i < 1000; $i++) {
    $service->findActiveBySymbol('BTCUSDT');
    if ($i % 100 == 0 && $i > 0) {
        echo "Completed $i requests\n";
    }
}
$totalTime = microtime(true) - $start;

echo "\n=== Stress Test Results ===\n";
echo "Total requests: 1000\n";
echo "Total time: " . round($totalTime, 2) . "s\n";
echo "Avg per request: " . round(($totalTime / 1000) * 1000, 2) . "ms\n";
echo "Requests per second: " . round(1000 / $totalTime, 0) . "\n";

$stats = $service->getCacheStats();
print_r($stats);

exit
```

**✅ PASS CRITERIA**: 
- Valid symbols are found
- Invalid symbols return null (not exception)
- Warm cache <2ms consistently
- Performance improvement >25x
- Stress test completes without errors
- Hit rate >95% in stress test

---

## 🎯 FINAL VERIFICATION CHECKLIST

After running all tests, verify:

```bash
# 1. Check Redis is running
redis-cli ping
# Expected: PONG

# 2. Check cache keys exist
redis-cli KEYS "instrument:*"
# Expected: List of cached instruments

# 3. Check logs for any errors
tail -n 100 storage/logs/laravel.log | grep -i error
# Expected: No errors related to cache

# 4. Verify scheduler
php artisan schedule:list
# Expected: warm-instrument-cache listed

# 5. Check Horizon (if accessible)
# Visit: http://localhost:8000/horizon
# Expected: Dashboard loads (with auth)

# 6. Final cache stats
php artisan tinker
```
```php
$service = app(\App\Domain\Market\Services\InstrumentService::class);
$stats = $service->getCacheStats();
print_r($stats);

// Should show good hit rate
$hitRate = (float) str_replace('%', '', $stats['hit_rate']);
echo "\nFinal hit rate: {$hitRate}%\n";
echo "Status: " . ($hitRate > 80 ? "✓ EXCELLENT" : "⚠ NEEDS TUNING") . "\n";

exit
```

---

## 📊 EXPECTED RESULTS SUMMARY

| Test Suite | Tests | Expected Pass Rate |
|------------|-------|--------------------|
| Configuration | 5 | 100% |
| Helper Functions | 14 | 100% |
| InstrumentService | 8 | 100% |
| Cache Invalidation | 3 | 100% |
| Cache Warming | 3 | 100% |
| Redis Health Check | 3 | 100% |
| Monitoring | 3 | 100% |
| Integration | 5 | 100% |
| **TOTAL** | **44** | **100%** |

---

## 🐛 TROUBLESHOOTING FAILED TESTS

### If Configuration Tests Fail:
```bash
# Check Redis
sudo systemctl status redis
# Start if needed: sudo systemctl start redis

# Clear and recache
php artisan config:clear
php artisan config:cache
```

### If Helper Functions Fail:
```bash
# Check helpers are loaded
php artisan tinker
>>> function_exists('format_number')
# Expected: true

# If false, check composer autoload
composer dump-autoload
```

### If Cache Tests Fail:
```bash
# Check Redis connection
redis-cli ping

# Check .env settings
cat .env | grep REDIS

# Test Redis manually
redis-cli
> SET test_key "test_value"
> GET test_key
> exit
```

### If Performance Tests Fail:
```bash
# Check Redis performance
redis-cli --latency
# Should show <1ms latency

# Check server load
top
# Verify CPU/Memory are not maxed
```

---

## ✅ TEST COMPLETION

After completing all tests, you should have:

- ✅ All 44 tests passing
- ✅ Cache hit rate >90%
- ✅ Warm cache <2ms
- ✅ 50-100x performance improvement
- ✅ Automatic invalidation working
- ✅ Redis fallback tested
- ✅ Monitoring and logging working

**Status**: Phase 0 improvements verified and production-ready! 🚀

---

**Next Steps**: Deploy to staging or continue to Phase 1
