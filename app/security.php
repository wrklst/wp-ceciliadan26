<?php

declare(strict_types=1);

/**
 * Security headers and CSP nonce.
 *
 * Configures Content Security Policy with nonces for inline scripts,
 * Trusted Types for DOM XSS prevention, and other security headers.
 * Skipped in wp-admin and during Vite dev server.
 */

namespace App;

/**
 * Generate a CSP nonce (once per request).
 */
function get_csp_nonce(): string
{
    static $nonce = null;

    if ($nonce === null) {
        $nonce = base64_encode(random_bytes(16));
    }

    return $nonce;
}

/**
 * Check if Vite dev server is running.
 */
function is_vite_dev(): bool
{
    static $isDev = null;

    if ($isDev === null) {
        $isDev = file_exists(get_template_directory() . '/public/hot');
    }

    return $isDev;
}

/**
 * Check if CSP headers should be skipped.
 */
function should_skip_csp(): bool
{
    return is_admin() || is_vite_dev();
}

/**
 * Send security headers including CSP with nonce.
 */
add_action('send_headers', function (): void {
    header_remove('X-Powered-By');

    if (should_skip_csp()) {
        return;
    }

    $nonce = get_csp_nonce();

    $directives = [
        "default-src 'self'",
        "script-src 'self' 'nonce-{$nonce}' 'unsafe-inline'",
        "style-src 'self' 'unsafe-inline'",
        "img-src 'self' data: https:",
        "font-src 'self'",
        "connect-src 'self' " . site_url(),
        "object-src 'none'",
        "frame-ancestors 'none'",
        "base-uri 'self'",
        "form-action 'self'",
        "trusted-types matomo",
        "require-trusted-types-for 'script'",
        "upgrade-insecure-requests",
    ];

    header('Content-Security-Policy: ' . implode('; ', $directives));

    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=(), bluetooth=(), magnetometer=(), gyroscope=(), accelerometer=(), ambient-light-sensor=(), autoplay=(), browsing-topics=(), display-capture=(), encrypted-media=(), fullscreen=(), idle-detection=(), midi=(), picture-in-picture=(), publickey-credentials-get=(), screen-wake-lock=(), serial=(), sync-xhr=(), unload=(), xr-spatial-tracking=()');

    header('Cross-Origin-Opener-Policy: same-origin');

    // TODO: Uncomment when deployed to production with HTTPS
    // header('Strict-Transport-Security: max-age=63072000; includeSubDomains; preload');
});

/**
 * Add CSP nonce to enqueued script tags.
 */
add_filter('script_loader_tag', function (string $tag, string $handle): string {
    if (should_skip_csp()) {
        return $tag;
    }

    $nonce = get_csp_nonce();

    if ($nonce && ! str_contains($tag, 'nonce=')) {
        $tag = str_replace('<script', '<script nonce="' . esc_attr($nonce) . '"', $tag);
    }

    return $tag;
}, 10, 2);

/**
 * Add CSP nonce to inline scripts (wp_add_inline_script).
 */
add_filter('wp_inline_script_attributes', function (array $attributes): array {
    if (should_skip_csp()) {
        return $attributes;
    }

    $attributes['nonce'] = get_csp_nonce();

    return $attributes;
});
