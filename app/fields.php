<?php

declare(strict_types=1);

/**
 * ACF configuration.
 *
 * Field groups are managed via the admin UI and synced
 * to acf-json/ for version control.
 *
 * Naming convention:
 *   Group keys:  group_{context}
 *   Field keys:  field_{context}_{name}
 *   Field names: {context}_{name}
 */

namespace App;

/**
 * Register ACF Options Page for global site settings.
 */
add_action('acf/init', function () {
    if (! function_exists('acf_add_options_page')) {
        return;
    }

    acf_add_options_page([
        'page_title' => 'Footer',
        'menu_title' => 'Footer',
        'menu_slug' => 'footer',
        'capability' => 'edit_posts',
        'redirect' => false,
        'icon_url' => 'dashicons-admin-generic',
        'position' => 22,
    ]);
});

/**
 * Set ACF JSON save/load paths to the theme's acf-json directory.
 */
add_filter('acf/settings/save_json', function () {
    return get_stylesheet_directory() . '/acf-json';
});

add_filter('acf/settings/load_json', function ($paths) {
    $paths[] = get_stylesheet_directory() . '/acf-json';

    return $paths;
});

/**
 * Hide the SEO meta description field on excluded pages.
 */
add_filter('acf/prepare_field/key=field_seo_meta_description', function ($field) {
    $post = get_post();

    if (! $post) {
        return $field;
    }

    $excluded_slugs = ['site-notice', 'privacy-policy'];

    if (in_array($post->post_name, $excluded_slugs, true)) {
        return false;
    }

    return $field;
});
