<?php

declare(strict_types=1);

/**
 * Image pipeline configuration.
 *
 * Custom image sizes, srcset filtering, format quality,
 * and big image threshold for an art advisory site.
 * ShortPixel handles WebP/AVIF conversion via .htaccess.
 */

namespace App;

/**
 * Register custom image sizes.
 *
 * Minimal set: small (mobile/cards), medium (content), large (hero/retina).
 */
add_action('after_setup_theme', function (): void {
    add_image_size('content-small', 640, 9999, false);
    add_image_size('content-medium', 1280, 9999, false);
    add_image_size('content-large', 1920, 9999, false);
}, 25);

/**
 * Disable default WordPress image sizes not used by the theme.
 */
add_filter('intermediate_image_sizes_advanced', function (array $sizes): array {
    unset($sizes['thumbnail']);
    unset($sizes['medium']);
    unset($sizes['medium_large']);
    unset($sizes['large']);
    unset($sizes['1536x1536']);
    unset($sizes['2048x2048']);

    return $sizes;
});

/**
 * Add custom sizes to the Media Library picker.
 */
add_filter('image_size_names_choose', function (array $sizes): array {
    return array_merge($sizes, [
        'content-small' => __('Small (640px)', 'sage'),
        'content-medium' => __('Medium (1280px)', 'sage'),
        'content-large' => __('Large (1920px)', 'sage'),
    ]);
});

/**
 * Filter srcset to only include registered widths.
 */
add_filter('wp_calculate_image_srcset', function (array $sources): array {
    $allowed_widths = [640, 1280, 1920];

    return array_filter($sources, function (array $source) use ($allowed_widths): bool {
        return in_array((int) $source['value'], $allowed_widths, true);
    });
});

/**
 * Set image quality per format.
 */
add_filter('wp_editor_set_quality', function (int $quality, string $mime_type): int {
    return match ($mime_type) {
        'image/avif' => 68,
        'image/webp' => 80,
        'image/jpeg' => 82,
        default => $quality,
    };
}, 10, 2);

/**
 * Big image threshold — keep originals up to 3840px.
 */
add_filter('big_image_size_threshold', function (): int {
    return 3840;
});

/**
 * Raise max srcset width to match largest registered size.
 *
 * WordPress default is 2048px. Explicit cap prevents surprise
 * if a larger size is added later.
 */
add_filter('max_srcset_image_width', function (): int {
    return 1920;
});

/**
 * Disable WP auto-sizes and its inline CSS.
 */
add_filter('wp_img_tag_add_auto_sizes', '__return_false');
