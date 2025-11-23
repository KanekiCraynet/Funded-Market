#!/usr/bin/env php
<?php

/**
 * Sanctum System Testing Script
 * 
 * Tests the enhanced Sanctum authentication with:
 * - Token abilities/scopes
 * - Token expiration
 * - User status checking
 * - Middleware authentication
 */

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║         SANCTUM SYSTEM - COMPREHENSIVE TEST SUITE            ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Bootstrap Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Domain\Users\Models\User;
use App\Domain\Users\Enums\TokenAbility;
use App\Http\Middleware\SanctumApiAuthentication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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

// Create test user
$testUser = null;

try {
    $testUser = User::firstOrCreate(
        ['email' => 'sanctum-test@example.com'],
        [
            'name' => 'Sanctum Test User',
            'password' => Hash::make('password'),
            'is_active' => true,
            'email_verified' => true,
        ]
    );
    
    // Clean up old tokens
    $testUser->tokens()->delete();
    
    echo "Test user created/found: {$testUser->email}\n\n";
} catch (\Exception $e) {
    echo "❌ Failed to create test user: " . $e->getMessage() . "\n";
    exit(1);
}

// =================================================================
// TEST SUITE 1: TOKEN ABILITY ENUM
// =================================================================

echo "┌──────────────────────────────────────────────────────────────┐\n";
echo "│ TEST SUITE 1: Token Ability Enum                            │\n";
echo "└──────────────────────────────────────────────────────────────┘\n";

runTest('TokenAbility enum exists', function() {
    return enum_exists(TokenAbility::class);
}, $results);

runTest('TokenAbility has READ ability', function() {
    return TokenAbility::READ->value === 'read';
}, $results);

runTest('TokenAbility::readAbilities() returns array', function() {
    $abilities = TokenAbility::readAbilities();
    return is_array($abilities) && count($abilities) >= 5;
}, $results);

runTest('TokenAbility::writeAbilities() returns array', function() {
    $abilities = TokenAbility::writeAbilities();
    return is_array($abilities) && count($abilities) >= 3;
}, $results);

runTest('TokenAbility::userAbilities() combines read+write', function() {
    $abilities = TokenAbility::userAbilities();
    return is_array($abilities) && count($abilities) >= 8;
}, $results);

runTest('TokenAbility::adminAbilities() includes admin', function() {
    $abilities = TokenAbility::adminAbilities();
    return in_array('admin', $abilities);
}, $results);

// =================================================================
// TEST SUITE 2: USER MODEL ENHANCEMENTS
// =================================================================

echo "\n";
echo "┌──────────────────────────────────────────────────────────────┐\n";
echo "│ TEST SUITE 2: User Model Token Methods                      │\n";
echo "└──────────────────────────────────────────────────────────────┘\n";

runTest('User has createApiToken method', function() use ($testUser) {
    return method_exists($testUser, 'createApiToken');
}, $results);

runTest('User has createReadOnlyToken method', function() use ($testUser) {
    return method_exists($testUser, 'createReadOnlyToken');
}, $results);

runTest('User has createTokenWithAbilities method', function() use ($testUser) {
    return method_exists($testUser, 'createTokenWithAbilities');
}, $results);

runTest('User has revokeAllTokens method', function() use ($testUser) {
    return method_exists($testUser, 'revokeAllTokens');
}, $results);

runTest('User has isActive method', function() use ($testUser) {
    return method_exists($testUser, 'isActive');
}, $results);

runTest('User isActive returns true', function() use ($testUser) {
    return $testUser->isActive() === true;
}, $results);

// =================================================================
// TEST SUITE 3: TOKEN CREATION
// =================================================================

echo "\n";
echo "┌──────────────────────────────────────────────────────────────┐\n";
echo "│ TEST SUITE 3: Token Creation & Abilities                    │\n";
echo "└──────────────────────────────────────────────────────────────┘\n";

runTest('Create standard API token', function() use ($testUser) {
    $tokenResult = $testUser->createApiToken('test-standard');
    
    if (!$tokenResult->plainTextToken) {
        return "No plain text token returned";
    }
    
    if (!$tokenResult->accessToken) {
        return "No access token model";
    }
    
    return true;
}, $results);

runTest('Standard token has user abilities', function() use ($testUser) {
    $tokenResult = $testUser->createApiToken('test-abilities');
    $abilities = $tokenResult->accessToken->abilities;
    
    $expectedAbilities = TokenAbility::userAbilities();
    
    // Check if token has expected abilities
    foreach ($expectedAbilities as $ability) {
        if (!in_array($ability, $abilities)) {
            return "Missing ability: {$ability}";
        }
    }
    
    return true;
}, $results);

runTest('Create read-only token', function() use ($testUser) {
    $tokenResult = $testUser->createReadOnlyToken('test-readonly');
    $abilities = $tokenResult->accessToken->abilities;
    
    $readAbilities = TokenAbility::readAbilities();
    $writeAbilities = TokenAbility::writeAbilities();
    
    // Should have read abilities
    foreach ($readAbilities as $ability) {
        if (!in_array($ability, $abilities)) {
            return "Missing read ability: {$ability}";
        }
    }
    
    // Should NOT have write abilities
    foreach ($writeAbilities as $ability) {
        if (in_array($ability, $abilities)) {
            return "Should not have write ability: {$ability}";
        }
    }
    
    return true;
}, $results);

runTest('Create token with custom abilities', function() use ($testUser) {
    $customAbilities = ['read:market', 'create:analysis'];
    $tokenResult = $testUser->createTokenWithAbilities('test-custom', $customAbilities);
    $abilities = $tokenResult->accessToken->abilities;
    
    if (!in_array('read:market', $abilities)) {
        return "Missing custom ability: read:market";
    }
    
    if (!in_array('create:analysis', $abilities)) {
        return "Missing custom ability: create:analysis";
    }
    
    return true;
}, $results);

runTest('Create token with expiration', function() use ($testUser) {
    $expiresAt = now()->addDays(7);
    $tokenResult = $testUser->createApiToken('test-expiry', $expiresAt);
    
    $tokenExpiry = $tokenResult->accessToken->expires_at;
    
    if (!$tokenExpiry) {
        return "Token has no expiration date";
    }
    
    // Check if expiry is approximately 7 days from now
    $diffInDays = now()->diffInDays($tokenExpiry);
    
    if ($diffInDays < 6 || $diffInDays > 8) {
        return "Expiration date incorrect: {$diffInDays} days";
    }
    
    return true;
}, $results);

// =================================================================
// TEST SUITE 4: TOKEN MANAGEMENT
// =================================================================

echo "\n";
echo "┌──────────────────────────────────────────────────────────────┐\n";
echo "│ TEST SUITE 4: Token Management                              │\n";
echo "└──────────────────────────────────────────────────────────────┘\n";

runTest('Count user tokens', function() use ($testUser) {
    $count = $testUser->tokens()->count();
    return $count >= 5; // Should have tokens from previous tests
}, $results);

runTest('Get active tokens', function() use ($testUser) {
    $activeTokens = $testUser->activeTokens()->get();
    return $activeTokens->count() >= 5;
}, $results);

runTest('Revoke specific token', function() use ($testUser) {
    $token = $testUser->createApiToken('test-revoke');
    $tokenId = $token->accessToken->id;
    
    $result = $testUser->revokeToken($tokenId);
    
    if (!$result) {
        return "Failed to revoke token";
    }
    
    // Verify token is deleted
    $tokenExists = $testUser->tokens()->where('id', $tokenId)->exists();
    
    if ($tokenExists) {
        return "Token still exists after revocation";
    }
    
    return true;
}, $results);

runTest('Revoke all tokens', function() use ($testUser) {
    // Create some tokens
    $testUser->createApiToken('temp-1');
    $testUser->createApiToken('temp-2');
    $testUser->createApiToken('temp-3');
    
    // Revoke all
    $testUser->revokeAllTokens();
    
    // Check count
    $count = $testUser->tokens()->count();
    
    if ($count !== 0) {
        return "Still has {$count} tokens after revokeAllTokens";
    }
    
    return true;
}, $results);

// =================================================================
// TEST SUITE 5: MIDDLEWARE AUTHENTICATION
// =================================================================

echo "\n";
echo "┌──────────────────────────────────────────────────────────────┐\n";
echo "│ TEST SUITE 5: Middleware Authentication                     │\n";
echo "└──────────────────────────────────────────────────────────────┘\n";

runTest('SanctumApiAuthentication middleware exists', function() {
    return class_exists(SanctumApiAuthentication::class);
}, $results);

runTest('Middleware handles valid token', function() use ($testUser) {
    // Create token
    $tokenResult = $testUser->createApiToken('middleware-test');
    $plainToken = $tokenResult->plainTextToken;
    
    // Create request with token
    $request = Request::create('/test', 'GET');
    $request->headers->set('Authorization', "Bearer {$plainToken}");
    
    // Create middleware
    $middleware = new SanctumApiAuthentication();
    
    // Handle request
    $response = $middleware->handle($request, function($req) {
        return response()->json(['success' => true]);
    });
    
    // Should pass through
    if ($response->getStatusCode() !== 200) {
        return "Expected 200, got " . $response->getStatusCode();
    }
    
    // Check if user was set
    if (!$request->user()) {
        return "User was not set on request";
    }
    
    return true;
}, $results);

runTest('Middleware rejects missing token', function() {
    $request = Request::create('/test', 'GET');
    // No Authorization header
    
    $middleware = new SanctumApiAuthentication();
    
    $response = $middleware->handle($request, function($req) {
        return response()->json(['success' => true]);
    });
    
    if ($response->getStatusCode() !== 401) {
        return "Expected 401, got " . $response->getStatusCode();
    }
    
    $data = json_decode($response->getContent(), true);
    
    if ($data['success'] !== false) {
        return "Expected success=false";
    }
    
    return true;
}, $results);

runTest('Middleware rejects invalid token', function() {
    $request = Request::create('/test', 'GET');
    $request->headers->set('Authorization', 'Bearer invalid-token-12345');
    
    $middleware = new SanctumApiAuthentication();
    
    $response = $middleware->handle($request, function($req) {
        return response()->json(['success' => true]);
    });
    
    if ($response->getStatusCode() !== 401) {
        return "Expected 401, got " . $response->getStatusCode();
    }
    
    return true;
}, $results);

runTest('Middleware checks user active status', function() use ($testUser) {
    // Create token
    $tokenResult = $testUser->createApiToken('active-check');
    $plainToken = $tokenResult->plainTextToken;
    
    // Deactivate user
    $testUser->update(['is_active' => false]);
    
    $request = Request::create('/test', 'GET');
    $request->headers->set('Authorization', "Bearer {$plainToken}");
    
    $middleware = new SanctumApiAuthentication();
    
    $response = $middleware->handle($request, function($req) {
        return response()->json(['success' => true]);
    });
    
    // Should return 403 Forbidden
    if ($response->getStatusCode() !== 403) {
        // Reactivate user before failing
        $testUser->update(['is_active' => true]);
        return "Expected 403, got " . $response->getStatusCode();
    }
    
    // Reactivate user
    $testUser->update(['is_active' => true]);
    
    return true;
}, $results);

// =================================================================
// TEST SUITE 6: TOKEN EXPIRATION
// =================================================================

echo "\n";
echo "┌──────────────────────────────────────────────────────────────┐\n";
echo "│ TEST SUITE 6: Token Expiration                              │\n";
echo "└──────────────────────────────────────────────────────────────┘\n";

runTest('Expired token is detected', function() use ($testUser) {
    // Create token that expired yesterday
    $tokenResult = $testUser->createApiToken('expired-test', now()->subDay());
    $plainToken = $tokenResult->plainTextToken;
    
    $request = Request::create('/test', 'GET');
    $request->headers->set('Authorization', "Bearer {$plainToken}");
    
    $middleware = new SanctumApiAuthentication();
    
    $response = $middleware->handle($request, function($req) {
        return response()->json(['success' => true]);
    });
    
    // Should return 401
    if ($response->getStatusCode() !== 401) {
        return "Expected 401 for expired token, got " . $response->getStatusCode();
    }
    
    $data = json_decode($response->getContent(), true);
    
    if (strpos(strtolower($data['message']), 'expired') === false) {
        return "Error message should mention expiration";
    }
    
    return true;
}, $results);

runTest('Non-expired token works', function() use ($testUser) {
    // Create token that expires in future
    $tokenResult = $testUser->createApiToken('future-expiry', now()->addDays(7));
    $plainToken = $tokenResult->plainTextToken;
    
    $request = Request::create('/test', 'GET');
    $request->headers->set('Authorization', "Bearer {$plainToken}");
    
    $middleware = new SanctumApiAuthentication();
    
    $response = $middleware->handle($request, function($req) {
        return response()->json(['success' => true]);
    });
    
    if ($response->getStatusCode() !== 200) {
        return "Expected 200, got " . $response->getStatusCode();
    }
    
    return true;
}, $results);

// =================================================================
// TEST SUITE 7: MIDDLEWARE REGISTRATION
// =================================================================

echo "\n";
echo "┌──────────────────────────────────────────────────────────────┐\n";
echo "│ TEST SUITE 7: Middleware Registration                       │\n";
echo "└──────────────────────────────────────────────────────────────┘\n";

runTest('sanctum.api middleware is registered', function() {
    $router = app('router');
    $middlewares = $router->getMiddleware();
    
    if (!isset($middlewares['sanctum.api'])) {
        return "sanctum.api middleware not found in router";
    }
    
    return true;
}, $results);

runTest('simple.auth middleware still exists (backward compat)', function() {
    $router = app('router');
    $middlewares = $router->getMiddleware();
    
    if (!isset($middlewares['simple.auth'])) {
        return "simple.auth middleware not found (should exist for backward compatibility)";
    }
    
    return true;
}, $results);

// =================================================================
// CLEANUP
// =================================================================

echo "\n";
echo "Cleaning up test data...\n";

try {
    $testUser->tokens()->delete();
    echo "✓ Test tokens cleaned up\n";
} catch (\Exception $e) {
    echo "⚠ Warning: Could not clean up tokens: " . $e->getMessage() . "\n";
}

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
    echo "Status: ✅ ALL TESTS PASSED - SANCTUM SYSTEM READY!\n";
} elseif ($results['failed'] === 0) {
    echo "Status: ⚠️  TESTS PASSED WITH WARNINGS\n";
} else {
    echo "Status: ❌ TESTS FAILED - REVIEW ISSUES ABOVE\n";
}

echo "\n";

// Save report
$reportFile = __DIR__ . '/storage/logs/sanctum_test_report_' . date('Y-m-d_H-i-s') . '.txt';
$report = "SANCTUM SYSTEM TEST REPORT\n";
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
