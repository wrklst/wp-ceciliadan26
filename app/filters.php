<?php

/**
 * Theme filters.
 */

namespace App;

/**
 * Disable XML-RPC at the application level.
 */
add_filter('xmlrpc_enabled', '__return_false');

/**
 * Restrict REST API access to authenticated users.
 *
 * Allow public access only to endpoints needed by the frontend.
 */
add_filter('rest_authentication_errors', function ($result) {
    if ($result !== null) {
        return $result;
    }

    if (is_user_logged_in()) {
        return $result;
    }

    $public_routes = [
        '/wp/v2/pages',
        '/wp/v2/posts',
        '/wp/v2/media',
        '/wp/v2/categories',
        '/wp/v2/tags',
    ];

    $request_uri = $_SERVER['REQUEST_URI'] ?? '';

    foreach ($public_routes as $route) {
        if (str_contains($request_uri, $route)) {
            return $result;
        }
    }

    return new \WP_Error(
        'rest_not_logged_in',
        __('You are not currently logged in.', 'sage'),
        ['status' => 401]
    );
});

/**
 * Remove /wp/v2/users endpoint for unauthenticated requests.
 */
add_filter('rest_endpoints', function ($endpoints) {
    if (! is_user_logged_in()) {
        unset($endpoints['/wp/v2/users']);
        unset($endpoints['/wp/v2/users/(?P<id>[\d]+)']);
    }

    return $endpoints;
});

/**
 * Block user enumeration via ?author=N query parameter.
 */
add_action('template_redirect', function () {
    if (is_author()) {
        wp_safe_redirect(home_url('/'), 301);
        exit;
    }
});

/**
 * Remove author data from oEmbed responses.
 */
add_filter('oembed_response_data', function ($data) {
    unset($data['author_name']);
    unset($data['author_url']);

    return $data;
});

/**
 * Customize the title separator.
 */
add_filter('document_title_separator', function () {
    return '–';
});

/**
 * Clean the front page title — site name only, no tagline.
 */
add_filter('document_title_parts', function ($title) {
    if (is_front_page()) {
        unset($title['tagline']);
    }

    return $title;
});

/**
 * Disable default WordPress image sizes not used by the theme.
 *
 * Only keeps sizes explicitly registered by the theme.
 */
add_filter('intermediate_image_sizes_advanced', function ($sizes) {
    unset($sizes['thumbnail']);
    unset($sizes['medium']);
    unset($sizes['medium_large']);
    unset($sizes['large']);
    unset($sizes['1536x1536']);
    unset($sizes['2048x2048']);

    return $sizes;
});

/**
 * Filter unused default widths from srcset at render time.
 *
 * Prevents legacy uploads (before the size filter above) from
 * including widths the theme never uses.
 */
add_filter('wp_calculate_image_srcset', function ($sources) {
    $remove_widths = [150, 300, 768, 1024, 1536, 2048];

    foreach ($remove_widths as $width) {
        unset($sources[$width]);
    }

    return $sources;
});

/**
 * Set image quality per format.
 */
add_filter('wp_editor_set_quality', function ($quality, $mime_type) {
    return match ($mime_type) {
        'image/avif' => 68,
        'image/webp' => 80,
        'image/jpeg' => 82,
        default => $quality,
    };
}, 10, 2);

/**
 * Increase the big image threshold for portfolio images.
 */
add_filter('big_image_size_threshold', function () {
    return 3840;
});

/**
 * Disable WordPress default sitemap for pages that should not be indexed.
 *
 * Excludes legal/utility pages from the sitemap. Add page slugs as needed.
 */
add_filter('wp_sitemaps_posts_query_args', function ($args, $post_type) {
    if ($post_type === 'page') {
        $exclude_slugs = ['privacy-policy', 'terms-of-service', 'legal'];

        $exclude_ids = array_filter(array_map(function ($slug) {
            $page = get_page_by_path($slug);

            return $page ? $page->ID : null;
        }, $exclude_slugs));

        if (! empty($exclude_ids)) {
            $args['post__not_in'] = array_merge($args['post__not_in'] ?? [], $exclude_ids);
        }
    }

    return $args;
}, 10, 2);
