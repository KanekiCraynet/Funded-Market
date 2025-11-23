<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security Headers Middleware
 * 
 * Adds comprehensive security headers to all HTTP responses:
 * - Content Security Policy (CSP)
 * - X-Frame-Options (Clickjacking protection)
 * - X-Content-Type-Options (MIME sniffing protection)
 * - Strict-Transport-Security (HTTPS enforcement)
 * - X-XSS-Protection (XSS filter)
 * - Referrer-Policy (Referrer information control)
 * - Permissions-Policy (Feature policy)
 * 
 * @see https://owasp.org/www-project-secure-headers/
 */
class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Add security headers
        $this->addContentSecurityPolicy($response, $request);
        $this->addXFrameOptions($response);
        $this->addXContentTypeOptions($response);
        $this->addStrictTransportSecurity($response);
        $this->addXXSSProtection($response);
        $this->addReferrerPolicy($response);
        $this->addPermissionsPolicy($response);
        $this->addAdditionalSecurityHeaders($response);

        return $response;
    }

    /**
     * Add Content-Security-Policy header
     * 
     * Prevents XSS, clickjacking, and other code injection attacks
     */
    protected function addContentSecurityPolicy(Response $response, Request $request): void
    {
        // Check if this is an API request
        $isApi = $request->is('api/*') || $request->expectsJson();

        if ($isApi) {
            // Strict CSP for API endpoints
            $csp = implode('; ', [
                "default-src 'none'",
                "frame-ancestors 'none'",
                "base-uri 'none'",
            ]);
        } else {
            // CSP for web pages
            $csp = implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline' 'unsafe-eval'", // Laravel Mix/Vite compatibility
                "style-src 'self' 'unsafe-inline'",
                "img-src 'self' data: https:",
                "font-src 'self' data:",
                "connect-src 'self'",
                "frame-src 'none'",
                "frame-ancestors 'none'",
                "object-src 'none'",
                "base-uri 'self'",
                "form-action 'self'",
                "upgrade-insecure-requests",
            ]);
        }

        $response->headers->set('Content-Security-Policy', $csp);
    }

    /**
     * Add X-Frame-Options header
     * 
     * Prevents clickjacking attacks
     */
    protected function addXFrameOptions(Response $response): void
    {
        $response->headers->set('X-Frame-Options', 'DENY');
    }

    /**
     * Add X-Content-Type-Options header
     * 
     * Prevents MIME type sniffing
     */
    protected function addXContentTypeOptions(Response $response): void
    {
        $response->headers->set('X-Content-Type-Options', 'nosniff');
    }

    /**
     * Add Strict-Transport-Security (HSTS) header
     * 
     * Forces HTTPS connections
     * Only added if request is over HTTPS
     */
    protected function addStrictTransportSecurity(Response $response): void
    {
        // Only add HSTS if we're on HTTPS
        if (request()->secure() || config('app.env') === 'production') {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }
    }

    /**
     * Add X-XSS-Protection header
     * 
     * Enables browser XSS filter (legacy browsers)
     * Modern browsers use CSP instead
     */
    protected function addXXSSProtection(Response $response): void
    {
        $response->headers->set('X-XSS-Protection', '1; mode=block');
    }

    /**
     * Add Referrer-Policy header
     * 
     * Controls referrer information sent with requests
     */
    protected function addReferrerPolicy(Response $response): void
    {
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    /**
     * Add Permissions-Policy header
     * 
     * Controls which browser features can be used
     */
    protected function addPermissionsPolicy(Response $response): void
    {
        $policy = implode(', ', [
            'accelerometer=()',
            'ambient-light-sensor=()',
            'autoplay=()',
            'battery=()',
            'camera=()',
            'display-capture=()',
            'document-domain=()',
            'encrypted-media=()',
            'execution-while-not-rendered=()',
            'execution-while-out-of-viewport=()',
            'fullscreen=()',
            'geolocation=()',
            'gyroscope=()',
            'layout-animations=()',
            'legacy-image-formats=()',
            'magnetometer=()',
            'microphone=()',
            'midi=()',
            'navigation-override=()',
            'payment=()',
            'picture-in-picture=()',
            'publickey-credentials-get=()',
            'speaker-selection=()',
            'sync-xhr=()',
            'usb=()',
            'vr=()',
            'wake-lock=()',
            'web-share=()',
            'xr-spatial-tracking=()',
        ]);

        $response->headers->set('Permissions-Policy', $policy);
    }

    /**
     * Add additional security headers
     */
    protected function addAdditionalSecurityHeaders(Response $response): void
    {
        // Remove server information
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        // Add CORS headers for API (if needed)
        if (request()->is('api/*')) {
            // Note: CORS should be handled by Laravel's CORS middleware
            // These are just additional security markers
            $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        }

        // Add Cross-Origin policies
        $response->headers->set('Cross-Origin-Embedder-Policy', 'require-corp');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');
    }
}
