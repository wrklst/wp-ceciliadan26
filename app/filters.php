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
        '/wp/v2/media',
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
 * Add target="_blank" and rel="noopener" to external links in content.
 */
add_filter('the_content', __NAMESPACE__ . '\\externalize_links');
add_filter('acf_the_content', __NAMESPACE__ . '\\externalize_links');

function externalize_links(string $content): string
{
    if (! $content) {
        return $content;
    }

    $site_host = wp_parse_url(home_url(), PHP_URL_HOST);

    return preg_replace_callback('/<a\s([^>]*href=["\']https?:\/\/[^"\']+["\'][^>]*)>/i', function ($matches) use ($site_host) {
        $tag = $matches[0];

        if (preg_match('/href=["\']https?:\/\/([^"\'\/]+)/i', $tag, $href)) {
            $link_host = $href[1];

            if ($link_host !== $site_host && ! str_ends_with($link_host, '.' . $site_host)) {
                if (! str_contains($tag, 'target=')) {
                    $tag = str_replace('<a ', '<a target="_blank" ', $tag);
                }

                if (! str_contains($tag, 'rel=')) {
                    $tag = str_replace('<a ', '<a rel="noopener" ', $tag);
                }
            }
        }

        return $tag;
    }, $content);
}

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
 * Image sizes.
 *
 * Minimal set for an art advisory site: small (mobile/cards),
 * medium (content images), large (hero/retina). ShortPixel
 * handles WebP/AVIF conversion for all sizes.
 */
add_action('after_setup_theme', function () {
    add_image_size('content-small', 640, 9999, false);
    add_image_size('content-medium', 1280, 9999, false);
    add_image_size('content-large', 1920, 9999, false);
}, 25);

/**
 * Disable default WordPress image sizes not used by the theme.
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
 * Add custom sizes to the Media Library picker.
 */
add_filter('image_size_names_choose', function ($sizes) {
    return array_merge($sizes, [
        'content-small' => __('Small (640px)', 'sage'),
        'content-medium' => __('Medium (1280px)', 'sage'),
        'content-large' => __('Large (1920px)', 'sage'),
    ]);
});

/**
 * Filter srcset to only include our registered widths.
 */
add_filter('wp_calculate_image_srcset', function ($sources) {
    $allowed_widths = [640, 1280, 1920];

    return array_filter($sources, function ($source) use ($allowed_widths) {
        return in_array((int) $source['value'], $allowed_widths, true);
    });
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
 * Big image threshold — keep originals up to 3840px.
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
        $exclude_slugs = ['site-notice', 'privacy-policy', 'terms-of-service', 'legal'];

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
