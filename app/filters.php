<?php

declare(strict_types=1);

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
    if (is_404() || is_author()) {
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

/**
 * Add target="_blank" and rel="noopener" to external links in nav menus.
 */
add_filter('nav_menu_link_attributes', function ($atts) {
    $url = $atts['href'] ?? '';

    if (! $url || ! str_starts_with($url, 'http')) {
        return $atts;
    }

    $site_host = wp_parse_url(home_url(), PHP_URL_HOST);
    $link_host = wp_parse_url($url, PHP_URL_HOST);

    if ($link_host && $link_host !== $site_host && ! str_ends_with($link_host, '.' . $site_host)) {
        $atts['target'] = '_blank';
        $atts['rel'] = 'noopener';
    }

    return $atts;
});

/**
 * Add sr-only announcement to external nav menu links.
 */
add_filter('nav_menu_item_title', function ($title, $item) {
    $url = $item->url ?? '';

    if (! $url || ! str_starts_with($url, 'http')) {
        return $title;
    }

    $site_host = wp_parse_url(home_url(), PHP_URL_HOST);
    $link_host = wp_parse_url($url, PHP_URL_HOST);

    if ($link_host && $link_host !== $site_host && ! str_ends_with($link_host, '.' . $site_host)) {
        $title .= ' <span class="sr-only">' . __('(opens in new tab)', 'sage') . '</span>';
    }

    return $title;
}, 10, 2);

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
 * Remove users sitemap — prevents author URL enumeration.
 */
add_filter('wp_sitemaps_add_provider', function ($provider, $name) {
    return $name === 'users' ? false : $provider;
}, 10, 2);

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

/**
 * Custom robots.txt with AI crawler rules.
 */
add_filter('robots_txt', function ($output, $public) {
    if (! $public) {
        return $output;
    }

    $sitemap = home_url('/wp-sitemap.xml');

    return <<<ROBOTS
User-agent: *
Disallow: /wp-admin/
Allow: /wp-admin/admin-ajax.php

# AI training crawlers
User-agent: GPTBot
Allow: /

User-agent: ClaudeBot
Allow: /

User-agent: Google-Extended
Allow: /

User-agent: Meta-ExternalAgent
Allow: /

# AI search index crawlers
User-agent: OAI-SearchBot
Allow: /

User-agent: PerplexityBot
Allow: /

User-agent: Claude-SearchBot
Allow: /

# User-triggered AI crawlers
User-agent: ChatGPT-User
Allow: /

User-agent: Claude-User
Allow: /

User-agent: Perplexity-User
Allow: /

User-agent: Applebot-Extended
Allow: /

Sitemap: {$sitemap}
ROBOTS;
}, 10, 2);
