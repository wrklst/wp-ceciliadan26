<?php

/**
 * ACF field group registration.
 *
 * All field groups are defined in code for version control.
 *
 * Naming convention:
 *   Group keys:  group_cdfa_{context}
 *   Field keys:  field_cdfa_{context}_{name}
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
        'page_title' => 'Site Settings',
        'menu_title' => 'Site Settings',
        'menu_slug' => 'site-settings',
        'capability' => 'edit_posts',
        'redirect' => false,
        'icon_url' => 'dashicons-admin-generic',
        'position' => 2,
    ]);
});

add_action('acf/include_fields', function () {
    if (! function_exists('acf_add_local_field_group')) {
        return;
    }

    /**
     * Footer — Global settings via Options Page.
     */
    acf_add_local_field_group([
        'key' => 'group_cdfa_footer',
        'title' => 'Footer',
        'fields' => [
            [
                'key' => 'field_cdfa_footer_business_name',
                'label' => 'Business Name',
                'name' => 'footer_business_name',
                'type' => 'text',
                'instructions' => '',
                'default_value' => 'Cecilia Dan Fine Art',
            ],
            [
                'key' => 'field_cdfa_footer_address',
                'label' => 'Address',
                'name' => 'footer_address',
                'type' => 'textarea',
                'instructions' => 'Mailing address. Each line break is preserved.',
                'rows' => 3,
                'new_lines' => 'br',
                'default_value' => "P.O. Box 3210\nSanta Monica, CA 90403",
            ],
            [
                'key' => 'field_cdfa_footer_email',
                'label' => 'Email',
                'name' => 'footer_email',
                'type' => 'email',
                'instructions' => 'Linked contact email displayed in the footer.',
            ],
            [
                'key' => 'field_cdfa_footer_contact_link',
                'label' => 'Contact Link',
                'name' => 'footer_contact_link',
                'type' => 'page_link',
                'instructions' => 'Page or anchor link to the contact form (e.g., home page with #contact).',
                'post_type' => ['page'],
                'allow_null' => 1,
                'allow_archives' => 0,
            ],
            [
                'key' => 'field_cdfa_footer_tagline',
                'label' => 'Tagline',
                'name' => 'footer_tagline',
                'type' => 'text',
                'instructions' => 'One-liner contextualizing the business.',
                'placeholder' => 'e.g., Trusted guidance in the art market since 1990.',
            ],
            [
                'key' => 'field_cdfa_footer_disclaimer',
                'label' => 'Disclaimer',
                'name' => 'footer_disclaimer',
                'type' => 'text',
                'instructions' => 'Optional legal disclaimer. Leave empty to hide.',
                'placeholder' => 'e.g., We do not accept artist submissions.',
            ],
            [
                'key' => 'field_cdfa_footer_asa_url',
                'label' => 'ASA Profile URL',
                'name' => 'footer_asa_url',
                'type' => 'url',
                'instructions' => 'Link to the American Society of Appraisers membership or directory page.',
            ],
            [
                'key' => 'field_cdfa_footer_apaa_url',
                'label' => 'APAA Profile URL',
                'name' => 'footer_apaa_url',
                'type' => 'url',
                'instructions' => 'Link to the APAA directory page.',
                'default_value' => 'https://www.artadvisors.org/art-advisor-directory/p/cecilia-dan',
            ],
            [
                'key' => 'field_cdfa_footer_copyright',
                'label' => 'Copyright',
                'name' => 'footer_copyright',
                'type' => 'text',
                'instructions' => 'Copyright text. Use {year} as a placeholder for the current year.',
                'default_value' => '{year} Cecilia Dan Fine Art. All rights reserved.',
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'options_page',
                    'operator' => '==',
                    'value' => 'site-settings',
                ],
            ],
        ],
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'active' => true,
    ]);

    /**
     * SEO — Meta Description
     *
     * Used by seo.php for <meta name="description"> and OG/Twitter tags.
     * Hidden on site-notice and privacy-policy via acf/location filter below.
     */
    /**
     * Page Content — Flexible Content
     *
     * Modular content builder for all pages. Layouts are added
     * here as the site develops.
     */
    acf_add_local_field_group([
        'key' => 'group_cdfa_page_content',
        'title' => 'Page Content',
        'fields' => [
            [
                'key' => 'field_cdfa_page_content_sections',
                'label' => 'Sections',
                'name' => 'page_content_sections',
                'type' => 'flexible_content',
                'instructions' => '',
                'button_label' => 'Add Section',
                'layouts' => [
                    'text_block' => [
                        'key' => 'layout_cdfa_text_block',
                        'name' => 'text_block',
                        'label' => 'Text Block',
                        'display' => 'block',
                        'sub_fields' => [
                            [
                                'key' => 'field_cdfa_text_block_headline',
                                'label' => 'Headline',
                                'name' => 'headline',
                                'type' => 'text',
                                'instructions' => '',
                            ],
                            [
                                'key' => 'field_cdfa_text_block_headline_visible',
                                'label' => 'Show headline visually',
                                'name' => 'headline_visible',
                                'type' => 'true_false',
                                'instructions' => 'When off, the headline is hidden visually but remains accessible to screen readers, search engines, and AI.',
                                'default_value' => 1,
                                'ui' => 1,
                            ],
                            [
                                'key' => 'field_cdfa_text_block_content',
                                'label' => 'Content',
                                'name' => 'content',
                                'type' => 'wysiwyg',
                                'instructions' => '',
                                'tabs' => 'all',
                                'toolbar' => 'full',
                                'media_upload' => 0,
                            ],
                        ],
                    ],
                    'accordion_block' => [
                        'key' => 'layout_cdfa_accordion_block',
                        'name' => 'accordion_block',
                        'label' => 'Accordion Block',
                        'display' => 'block',
                        'sub_fields' => [
                            [
                                'key' => 'field_cdfa_accordion_block_headline',
                                'label' => 'Headline',
                                'name' => 'headline',
                                'type' => 'text',
                                'instructions' => '',
                            ],
                            [
                                'key' => 'field_cdfa_accordion_block_headline_visible',
                                'label' => 'Show headline visually',
                                'name' => 'headline_visible',
                                'type' => 'true_false',
                                'instructions' => 'When off, the headline is hidden visually but remains accessible to screen readers, search engines, and AI.',
                                'default_value' => 1,
                                'ui' => 1,
                            ],
                            [
                                'key' => 'field_cdfa_accordion_block_items',
                                'label' => 'Items',
                                'name' => 'items',
                                'type' => 'repeater',
                                'instructions' => '',
                                'button_label' => 'Add Item',
                                'layout' => 'block',
                                'sub_fields' => [
                                    [
                                        'key' => 'field_cdfa_accordion_item_headline',
                                        'label' => 'Headline',
                                        'name' => 'headline',
                                        'type' => 'text',
                                        'instructions' => '',
                                    ],
                                    [
                                        'key' => 'field_cdfa_accordion_item_hash',
                                        'label' => 'Hash',
                                        'name' => 'hash',
                                        'type' => 'text',
                                        'instructions' => 'URL fragment for direct linking (e.g., "advising" creates #advising). Lowercase, no spaces.',
                                        'placeholder' => 'advising',
                                    ],
                                    [
                                        'key' => 'field_cdfa_accordion_item_lead_text',
                                        'label' => 'Lead Text',
                                        'name' => 'lead_text',
                                        'type' => 'textarea',
                                        'instructions' => 'Short introductory paragraph shown when the accordion item is collapsed.',
                                        'rows' => 3,
                                        'new_lines' => 'br',
                                    ],
                                    [
                                        'key' => 'field_cdfa_accordion_item_copy_text',
                                        'label' => 'Copy Text',
                                        'name' => 'copy_text',
                                        'type' => 'textarea',
                                        'instructions' => 'Extended content revealed when the accordion item is expanded.',
                                        'rows' => 8,
                                        'new_lines' => 'wpautop',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'team_block' => [
                        'key' => 'layout_cdfa_team_block',
                        'name' => 'team_block',
                        'label' => 'Team Block',
                        'display' => 'block',
                        'sub_fields' => [
                            [
                                'key' => 'field_cdfa_team_block_headline',
                                'label' => 'Headline',
                                'name' => 'headline',
                                'type' => 'text',
                                'instructions' => '',
                            ],
                            [
                                'key' => 'field_cdfa_team_block_headline_visible',
                                'label' => 'Show headline visually',
                                'name' => 'headline_visible',
                                'type' => 'true_false',
                                'instructions' => 'When off, the headline is hidden visually but remains accessible to screen readers, search engines, and AI.',
                                'default_value' => 1,
                                'ui' => 1,
                            ],
                            [
                                'key' => 'field_cdfa_team_block_members',
                                'label' => 'Members',
                                'name' => 'members',
                                'type' => 'repeater',
                                'instructions' => '',
                                'button_label' => 'Add Member',
                                'layout' => 'block',
                                'sub_fields' => [
                                    [
                                        'key' => 'field_cdfa_team_member_name',
                                        'label' => 'Name',
                                        'name' => 'name',
                                        'type' => 'text',
                                        'instructions' => '',
                                    ],
                                    [
                                        'key' => 'field_cdfa_team_member_position',
                                        'label' => 'Position',
                                        'name' => 'position',
                                        'type' => 'text',
                                        'instructions' => '',
                                        'placeholder' => 'e.g., Art Advisor & Accredited Senior Appraiser',
                                    ],
                                    [
                                        'key' => 'field_cdfa_team_member_email',
                                        'label' => 'Email',
                                        'name' => 'email',
                                        'type' => 'email',
                                        'instructions' => '',
                                    ],
                                    [
                                        'key' => 'field_cdfa_team_member_lead_text',
                                        'label' => 'Lead Text',
                                        'name' => 'lead_text',
                                        'type' => 'textarea',
                                        'instructions' => 'Short introductory paragraph shown when collapsed.',
                                        'rows' => 3,
                                        'new_lines' => 'br',
                                    ],
                                    [
                                        'key' => 'field_cdfa_team_member_copy_text',
                                        'label' => 'Copy Text',
                                        'name' => 'copy_text',
                                        'type' => 'textarea',
                                        'instructions' => 'Extended biography revealed when expanded.',
                                        'rows' => 8,
                                        'new_lines' => 'wpautop',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'image_block' => [
                        'key' => 'layout_cdfa_image_block',
                        'name' => 'image_block',
                        'label' => 'Image Block',
                        'display' => 'block',
                        'sub_fields' => [
                            [
                                'key' => 'field_cdfa_image_block_headline',
                                'label' => 'Headline',
                                'name' => 'headline',
                                'type' => 'text',
                                'instructions' => '',
                            ],
                            [
                                'key' => 'field_cdfa_image_block_headline_visible',
                                'label' => 'Show headline visually',
                                'name' => 'headline_visible',
                                'type' => 'true_false',
                                'instructions' => 'When off, the headline is hidden visually but remains accessible to screen readers, search engines, and AI.',
                                'default_value' => 1,
                                'ui' => 1,
                            ],
                            [
                                'key' => 'field_cdfa_image_block_image',
                                'label' => 'Image',
                                'name' => 'image',
                                'type' => 'image',
                                'instructions' => '',
                                'return_format' => 'id',
                                'preview_size' => 'content-medium',
                                'library' => 'all',
                            ],
                            [
                                'key' => 'field_cdfa_image_block_caption',
                                'label' => 'Caption',
                                'name' => 'caption',
                                'type' => 'text',
                                'instructions' => 'Photo credit or description shown below the image.',
                            ],
                        ],
                    ],
                    'list_block' => [
                        'key' => 'layout_cdfa_list_block',
                        'name' => 'list_block',
                        'label' => 'List Block',
                        'display' => 'block',
                        'sub_fields' => [
                            [
                                'key' => 'field_cdfa_list_block_headline',
                                'label' => 'Headline',
                                'name' => 'headline',
                                'type' => 'text',
                                'instructions' => '',
                            ],
                            [
                                'key' => 'field_cdfa_list_block_headline_visible',
                                'label' => 'Show headline visually',
                                'name' => 'headline_visible',
                                'type' => 'true_false',
                                'instructions' => 'When off, the headline is hidden visually but remains accessible to screen readers, search engines, and AI.',
                                'default_value' => 1,
                                'ui' => 1,
                            ],
                            [
                                'key' => 'field_cdfa_list_block_text',
                                'label' => 'Text',
                                'name' => 'text',
                                'type' => 'textarea',
                                'instructions' => '',
                                'rows' => 3,
                                'new_lines' => 'wpautop',
                            ],
                            [
                                'key' => 'field_cdfa_list_block_text_visible',
                                'label' => 'Show text visually',
                                'name' => 'text_visible',
                                'type' => 'true_false',
                                'instructions' => 'When off, the text is hidden visually but remains accessible to screen readers, search engines, and AI.',
                                'default_value' => 1,
                                'ui' => 1,
                            ],
                            [
                                'key' => 'field_cdfa_list_block_groups',
                                'label' => 'Groups',
                                'name' => 'groups',
                                'type' => 'repeater',
                                'instructions' => '',
                                'button_label' => 'Add Group',
                                'layout' => 'block',
                                'sub_fields' => [
                                    [
                                        'key' => 'field_cdfa_list_group_headline',
                                        'label' => 'Headline',
                                        'name' => 'headline',
                                        'type' => 'text',
                                        'instructions' => '',
                                    ],
                                    [
                                        'key' => 'field_cdfa_list_group_items',
                                        'label' => 'Items',
                                        'name' => 'items',
                                        'type' => 'repeater',
                                        'instructions' => '',
                                        'button_label' => 'Add Item',
                                        'layout' => 'table',
                                        'sub_fields' => [
                                            [
                                                'key' => 'field_cdfa_list_item_name',
                                                'label' => 'Name',
                                                'name' => 'name',
                                                'type' => 'text',
                                                'instructions' => '',
                                            ],
                                            [
                                                'key' => 'field_cdfa_list_item_url',
                                                'label' => 'URL',
                                                'name' => 'url',
                                                'type' => 'url',
                                                'instructions' => '',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'page',
                ],
            ],
        ],
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'seamless',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'active' => true,
        'hide_on_screen' => [
            'the_content',
        ],
    ]);

    acf_add_local_field_group([
        'key' => 'group_cdfa_seo',
        'title' => 'SEO',
        'fields' => [
            [
                'key' => 'field_cdfa_seo_meta_description',
                'label' => 'Meta Description',
                'name' => 'meta_description',
                'type' => 'text',
                'instructions' => 'Shown in search results and social shares. Max 160 characters. Leave empty to auto-generate from page content.',
                'maxlength' => 160,
                'placeholder' => 'Concise description of this page for search engines and AI',
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'page',
                ],
            ],
        ],
        'menu_order' => 100,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'active' => true,
    ]);
});

/**
 * Hide the SEO field group on excluded pages.
 */
add_filter('acf/location/rule_match/type=post_type', function ($match, $rule, $screen, $field_group) {
    if (($field_group['key'] ?? '') !== 'group_cdfa_seo') {
        return $match;
    }

    $post_id = $screen['post_id'] ?? 0;

    if (! $post_id) {
        return $match;
    }

    $post = get_post($post_id);

    if (! $post) {
        return $match;
    }

    $excluded_slugs = ['site-notice', 'privacy-policy'];

    if (in_array($post->post_name, $excluded_slugs, true)) {
        return false;
    }

    return $match;
}, 10, 4);
