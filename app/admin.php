<?php

declare(strict_types=1);

/**
 * Admin UI customization.
 *
 * Removes blog and comment infrastructure from a pages-only site.
 */

namespace App;

/**
 * Remove Posts and Comments from the admin menu.
 */
add_action('admin_menu', function () {
    remove_menu_page('edit.php');
    remove_menu_page('edit-comments.php');

    global $menu;
    foreach ($menu as $key => $item) {
        if (($item[2] ?? '') === 'upload.php') {
            unset($menu[$key]);
            $menu[21] = $item;
            break;
        }
    }
});

/**
 * Remove Comments and New Post from the admin bar.
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
 * Hide the Posts post type from public routes and admin UI.
 */
add_filter('register_post_type_args', function (array $args, string $post_type): array {
    if ($post_type === 'post') {
        $args['public'] = false;
        $args['show_ui'] = false;
        $args['show_in_rest'] = false;
    }

    return $args;
}, 10, 2);
