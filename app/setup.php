<?php

/**
 * Theme setup.
 */

namespace App;

use Illuminate\Support\Facades\Vite;

/**
 * Inject styles into the block editor.
 *
 * @return array
 */
add_filter('block_editor_settings_all', function ($settings) {
    $style = Vite::asset('resources/css/editor.css');

    $settings['styles'][] = [
        'css' => "@import url('{$style}')",
    ];

    return $settings;
});

/**
 * Inject scripts into the block editor.
 *
 * @return void
 */
add_filter('admin_head', function () {
    if (! get_current_screen()?->is_block_editor()) {
        return;
    }

    $dependencies = json_decode(Vite::content('editor.deps.json'));

    foreach ($dependencies as $dependency) {
        if (! wp_script_is($dependency)) {
            wp_enqueue_script($dependency);
        }
    }

    echo Vite::withEntryPoints([
        'resources/js/editor.js',
    ])->toHtml();
});

/**
 * Use the generated theme.json file.
 *
 * @return string
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
    ]);

    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('responsive-embeds');
    add_theme_support('html5', [
        'caption',
        'gallery',
        'search-form',
        'script',
        'style',
    ]);

    // Disable comments site-wide
    remove_post_type_support('post', 'comments');
    remove_post_type_support('page', 'comments');
}, 20);

/**
 * Remove Posts and Comments from the admin.
 */
add_action('admin_menu', function () {
    remove_menu_page('edit.php');
    remove_menu_page('edit-comments.php');
});

/**
 * Remove Comments from the admin bar.
 */
add_action('wp_before_admin_bar_render', function () {
    global $wp_admin_bar;
    $wp_admin_bar->remove_menu('comments');
    $wp_admin_bar->remove_menu('new-post');
});

/**
 * Disable comments on the frontend.
 */
add_filter('comments_open', '__return_false');
add_filter('pings_open', '__return_false');

/**
 * Remove the Posts post type from REST API public routes.
 */
add_filter('register_post_type_args', function ($args, $post_type) {
    if ($post_type === 'post') {
        $args['public'] = false;
        $args['show_ui'] = false;
        $args['show_in_rest'] = false;
    }

    return $args;
}, 10, 2);

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
    remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10);
    remove_action('wp_head', 'feed_links', 2);
    remove_action('wp_head', 'feed_links_extra', 3);
    remove_action('wp_head', 'rest_output_link_wp_head', 10);
    remove_action('wp_head', 'wp_oembed_add_discovery_links');
    remove_action('wp_head', 'wp_site_icon', 99);

    // Remove emoji support
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
    add_filter('emoji_svg_url', '__return_false');

    // Remove global styles and SVG filters
    remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles');
    remove_action('wp_body_open', 'wp_global_styles_render_svg_filters');
    remove_action('wp_footer', 'wp_enqueue_stored_styles', 1);
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
 * Dequeue block editor styles on the frontend.
 */
add_action('wp_enqueue_scripts', function () {
    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('wp-block-library-theme');
    wp_dequeue_style('global-styles');
    wp_dequeue_style('classic-theme-styles');
}, 20);

/**
 * Disable WordPress speculation rules.
 */
add_filter('wp_speculation_rules_configuration', '__return_null');
add_action('wp_enqueue_scripts', function () {
    wp_deregister_script('wp-speculation-rules');
});
