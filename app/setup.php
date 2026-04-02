<?php

declare(strict_types=1);

/**
 * Theme setup.
 */

namespace App;

/**
 * Use the generated theme.json file.
 */
add_filter('theme_file_path', function ($path, $file) {
    return $file === 'theme.json'
        ? public_path('build/assets/theme.json')
        : $path;
}, 10, 2);

/**
 * Register the initial theme setup.
 *
 * @return void
 */
add_action('after_setup_theme', function () {
    remove_theme_support('block-templates');
    remove_theme_support('core-block-patterns');

    register_nav_menus([
        'primary_navigation' => __('Primary Navigation', 'sage'),
        'footer_navigation' => __('Footer Sitemap', 'sage'),
    ]);

    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('responsive-embeds');
    add_theme_support('html5', [
        'caption',
        'gallery',
        'script',
        'style',
    ]);

    remove_post_type_support('page', 'comments');
}, 20);

/**
 * Clean up wp_head output.
 *
 * Remove all non-essential WordPress output for a custom theme
 * that does not use the block editor on the frontend.
 */
add_action('init', function () {
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'wp_shortlink_wp_head', 10);
    remove_action('template_redirect', 'wp_shortlink_header', 11);
    remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10);
    remove_action('wp_head', 'feed_links', 2);
    remove_action('wp_head', 'feed_links_extra', 3);
    remove_action('wp_head', 'rest_output_link_wp_head', 10);
    remove_action('template_redirect', 'rest_output_link_header', 11);
    remove_action('wp_head', 'wp_oembed_add_discovery_links');
    remove_action('wp_head', 'wp_site_icon', 99);

    // Remove emoji support
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
    add_filter('emoji_svg_url', '__return_false');

    // Remove global styles and SVG filters
    remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles');
    remove_action('wp_footer', 'wp_enqueue_global_styles', 1);
    remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles_css_custom_properties');
    remove_action('wp_body_open', 'wp_global_styles_render_svg_filters');
    remove_action('wp_footer', 'wp_enqueue_stored_styles', 1);
    remove_action('wp_enqueue_scripts', ['WP_Duotone', 'output_global_styles'], 11);
});

/**
 * Remove DNS prefetch for s.w.org.
 */
add_filter('wp_resource_hints', function ($hints, $relation_type) {
    if ($relation_type === 'dns-prefetch') {
        $hints = array_filter($hints, function ($hint) {
            return ! str_contains($hint, 's.w.org');
        });
    }

    return $hints;
}, 10, 2);

/**
 * Remove block editor styles from the frontend.
 *
 * Deregister (not just dequeue) to prevent WP 6.9 from
 * re-enqueuing global styles after our dequeue runs.
 */
add_action('wp_enqueue_scripts', function () {
    wp_deregister_style('wp-block-library');
    wp_deregister_style('wp-block-library-theme');
    wp_deregister_style('global-styles');
    wp_deregister_style('global-styles-css-custom-properties');
    wp_deregister_style('classic-theme-styles');
    wp_deregister_style('wp-img-auto-sizes-contain');
}, 20);

/**
 * Disable WordPress speculation rules.
 */
add_filter('wp_speculation_rules_configuration', '__return_null');
add_action('wp_enqueue_scripts', function () {
    wp_deregister_script('wp-speculation-rules');
});
