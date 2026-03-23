<?php

/**
 * SEO, Open Graph, and JSON-LD structured data.
 *
 * Replaces the need for SEO plugins with ~100 lines of targeted code.
 * All values use proper escaping for their output context.
 */

namespace App;

/**
 * Output meta description and Open Graph tags in wp_head.
 */
add_action('wp_head', function () {
    $description = get_meta_description();
    $is_noindex = is_noindex_page();

    if ($description) {
        printf(
            '<meta name="description" content="%s">' . "\n",
            esc_attr($description)
        );
    }

    // Skip OG tags on noindexed pages
    if ($is_noindex) {
        return;
    }

    $title = wp_get_document_title();
    $url = is_front_page() ? home_url('/') : get_permalink();
    $site_name = get_bloginfo('name', 'display');
    $locale = get_locale();

    printf('<meta property="og:title" content="%s">' . "\n", esc_attr($title));
    printf('<meta property="og:type" content="%s">' . "\n", is_single() ? 'article' : 'website');
    printf('<meta property="og:url" content="%s">' . "\n", esc_url($url));
    printf('<meta property="og:site_name" content="%s">' . "\n", esc_attr($site_name));
    printf('<meta property="og:locale" content="%s">' . "\n", esc_attr($locale));

    if ($description) {
        printf('<meta property="og:description" content="%s">' . "\n", esc_attr($description));
    }

    // OG image — uses the featured image or a default OG image
    $og_image = get_og_image();

    if ($og_image) {
        printf('<meta property="og:image" content="%s">' . "\n", esc_url($og_image['url']));
        printf('<meta property="og:image:width" content="%s">' . "\n", esc_attr($og_image['width']));
        printf('<meta property="og:image:height" content="%s">' . "\n", esc_attr($og_image['height']));
        printf('<meta property="og:image:type" content="%s">' . "\n", esc_attr($og_image['type']));
        printf('<meta property="og:image:alt" content="%s">' . "\n", esc_attr($og_image['alt']));
    }

    // Twitter/X card
    printf('<meta name="twitter:card" content="summary_large_image">' . "\n");
    printf('<meta name="twitter:title" content="%s">' . "\n", esc_attr($title));

    if ($description) {
        printf('<meta name="twitter:description" content="%s">' . "\n", esc_attr($description));
    }

    if ($og_image) {
        printf('<meta name="twitter:image" content="%s">' . "\n", esc_url($og_image['url']));
        printf('<meta name="twitter:image:alt" content="%s">' . "\n", esc_attr($og_image['alt']));
    }
}, 1);

/**
 * Add noindex to legal/utility pages.
 */
add_action('wp_head', function () {
    if (is_noindex_page()) {
        echo '<meta name="robots" content="noindex, nofollow">' . "\n";
    }
}, 1);

/**
 * Output JSON-LD structured data in wp_footer.
 */
add_action('wp_footer', function () {
    $schema = [];

    // WebSite schema on every page
    $schema[] = [
        '@type' => 'WebSite',
        '@id' => home_url('/#website'),
        'url' => home_url('/'),
        'name' => get_bloginfo('name', 'raw'),
        'inLanguage' => get_locale(),
    ];

    // Organization schema on every page
    $schema[] = [
        '@type' => 'Organization',
        '@id' => home_url('/#organization'),
        'name' => 'Cecilia Dan Fine Art',
        'url' => home_url('/'),
        'description' => 'Art advisory, appraisals, collection management, and charitable donation consulting for collectors, family offices, institutions, and estates.',
        'address' => [
            '@type' => 'PostalAddress',
            'postOfficeBoxNumber' => '3210',
            'addressLocality' => 'Santa Monica',
            'addressRegion' => 'CA',
            'postalCode' => '90403',
            'addressCountry' => 'US',
        ],
        'telephone' => '+1-310-435-6870',
        'email' => 'cecilia.dan@mac.com',
        'founder' => ['@id' => home_url('/#person')],
        'knowsAbout' => [
            'Modern Art',
            'Contemporary Art',
            'Art Appraisals',
            'Collection Management',
            'Art Advisory',
            'USPAP Appraisals',
            'Charitable Art Donations',
        ],
        'memberOf' => [
            [
                '@type' => 'Organization',
                'name' => 'Association of Professional Art Advisors',
                'alternateName' => 'APAA',
            ],
            [
                '@type' => 'Organization',
                'name' => 'American Society of Appraisers',
                'alternateName' => 'ASA',
            ],
        ],
    ];

    // Person + ProfilePage on front page and about page
    if (is_front_page() || is_page('about')) {
        $person = [
            '@type' => 'Person',
            '@id' => home_url('/#person'),
            'name' => 'Cecilia Dan',
            'url' => home_url('/'),
            'jobTitle' => 'Art Advisor & Accredited Senior Appraiser',
            'worksFor' => ['@id' => home_url('/#organization')],
            'alumniOf' => [
                [
                    '@type' => 'CollegeOrUniversity',
                    'name' => 'University of California, Los Angeles',
                    'alternateName' => 'UCLA',
                ],
                [
                    '@type' => 'CollegeOrUniversity',
                    'name' => 'Claremont Graduate University',
                    'department' => 'Drucker School of Management',
                ],
            ],
            'knowsAbout' => [
                'Modern Art',
                'Contemporary Art',
                'Art Appraisals',
                'Art Advisory',
                'Collection Management',
                'USPAP Standards',
                'Charitable Art Donations',
                'Estate Appraisals',
            ],
            'sameAs' => [
                // Add when available:
                // 'https://www.instagram.com/ceciliadanfineart/',
                // 'https://www.linkedin.com/in/ceciliadan/',
                // 'https://www.apaaonline.com/' (APAA directory link),
            ],
            // 'image' => get_theme_file_uri('resources/images/cecilia-dan.jpg'),
        ];

        $schema[] = $person;

        if (is_front_page()) {
            $schema[] = [
                '@type' => 'ProfilePage',
                '@id' => home_url('/#profilepage'),
                'url' => home_url('/'),
                'name' => get_bloginfo('name', 'raw'),
                'mainEntity' => ['@id' => home_url('/#person')],
                'mainEntityOfPage' => home_url('/'),
                'inLanguage' => get_locale(),
                'dateCreated' => get_the_date('c', get_option('page_on_front')),
                'dateModified' => get_the_modified_date('c', get_option('page_on_front')),
            ];
        }
    }

    // Service schema on services page
    if (is_page('services')) {
        $services = [
            [
                'name' => 'Art Advisory & Acquisitions',
                'description' => 'Strategic guidance on acquisitions, artist identification, and collection building for collectors at every level. Includes market research, due diligence, negotiation, and access to work before it reaches the open market.',
            ],
            [
                'name' => 'Art Appraisals',
                'description' => 'USPAP-compliant professional appraisals for insurance, estate planning, charitable donations, equitable distribution, and market value consultation. Accredited Senior Appraiser (ASA) designation.',
            ],
            [
                'name' => 'Collection Management',
                'description' => 'Comprehensive collection stewardship including cataloging, documentation, conservation coordination, exhibition management, storage logistics, and long-term strategic planning.',
            ],
            [
                'name' => 'Charitable Donation Consulting',
                'description' => 'Guidance on donating artwork to museums and nonprofits, including institution selection, IRS compliance, qualified appraisals, and coordination with tax advisors to maximize philanthropic and financial impact.',
            ],
        ];

        foreach ($services as $service) {
            $schema[] = [
                '@type' => 'Service',
                'name' => $service['name'],
                'description' => $service['description'],
                'provider' => ['@id' => home_url('/#organization')],
                'areaServed' => [
                    '@type' => 'Country',
                    'name' => 'United States',
                ],
            ];
        }
    }

    if (! empty($schema)) {
        $ld = [
            '@context' => 'https://schema.org',
            '@graph' => $schema,
        ];

        printf(
            '<script type="application/ld+json">%s</script>' . "\n",
            wp_json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
    }
});

/**
 * Get the meta description for the current page.
 */
function get_meta_description(): string
{
    if (is_front_page()) {
        return get_bloginfo('description', 'display');
    }

    if (is_singular()) {
        $post = get_post();

        if (! $post) {
            return '';
        }

        // Use ACF field if available
        if (function_exists('get_field')) {
            $custom = get_field('meta_description', $post->ID);

            if ($custom) {
                return wp_strip_all_tags($custom);
            }
        }

        // Fall back to excerpt or trimmed content
        if ($post->post_excerpt) {
            return wp_strip_all_tags($post->post_excerpt);
        }

        return wp_trim_words(wp_strip_all_tags($post->post_content), 25, '');
    }

    if (is_archive()) {
        return wp_strip_all_tags(get_the_archive_description());
    }

    return '';
}

/**
 * Get the OG image data for the current page.
 */
function get_og_image(): ?array
{
    $image_id = null;

    // Try featured image on singular pages
    if (is_singular() && has_post_thumbnail()) {
        $image_id = get_post_thumbnail_id();
    }

    // Fall back to default OG image
    if (! $image_id) {
        $og_path = get_theme_file_path('resources/images/og-image.jpg');

        if (file_exists($og_path)) {
            return [
                'url' => get_theme_file_uri('resources/images/og-image.jpg'),
                'width' => '1200',
                'height' => '630',
                'type' => 'image/jpeg',
                'alt' => get_bloginfo('name', 'display'),
            ];
        }

        return null;
    }

    $src = wp_get_attachment_image_src($image_id, 'full');
    $alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);
    $type = get_post_mime_type($image_id);

    if (! $src) {
        return null;
    }

    return [
        'url' => $src[0],
        'width' => (string) $src[1],
        'height' => (string) $src[2],
        'type' => $type ?: 'image/jpeg',
        'alt' => $alt ?: get_the_title(),
    ];
}

/**
 * Check if the current page should be noindexed.
 *
 * Add page slugs to exclude from search engine indexing.
 */
function is_noindex_page(): bool
{
    if (! is_singular('page')) {
        return false;
    }

    $noindex_slugs = ['privacy-policy', 'terms-of-service', 'legal'];
    $post = get_post();

    return $post && in_array($post->post_name, $noindex_slugs, true);
}
