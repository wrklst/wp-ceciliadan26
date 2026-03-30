<?php

/**
 * SEO, Open Graph, and JSON-LD structured data.
 *
 * Replaces the need for SEO plugins. All values use proper escaping
 * for their output context.
 */

namespace App;

/**
 * Output meta description and Open Graph tags in wp_head.
 */
add_action('wp_head', function () {
    $is_noindex = is_noindex_page();

    if ($is_noindex) {
        return;
    }

    $description = get_meta_description();

    if ($description) {
        printf(
            '<meta name="description" content="%s">' . "\n",
            esc_attr($description)
        );
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
    $contact = get_field('contact_details', 'option');
    $schema = [];

    // WebSite schema on every page
    $schema[] = [
        '@type' => 'WebSite',
        '@id' => home_url('/#website'),
        'url' => home_url('/'),
        'name' => get_bloginfo('name', 'raw'),
        'inLanguage' => 'en-US',
    ];

    // ProfessionalService schema on every page
    $schema[] = [
        '@type' => 'ProfessionalService',
        '@id' => home_url('/#organization'),
        'name' => $contact['business_name'],
        'url' => home_url('/'),
        'description' => 'Cecilia Dan Fine Art partners with collectors, family offices, institutions, and estates to make confident, informed decisions in the art market. Based in Santa Monica, the firm provides trusted guidance on acquisitions, collection strategy, and appraisals throughout Los Angeles and nationally.',
        'address' => [
            '@type' => 'PostalAddress',
            'postOfficeBoxNumber' => '3210',
            'addressLocality' => 'Santa Monica',
            'addressRegion' => 'CA',
            'postalCode' => '90403',
            'addressCountry' => 'US',
        ],
        'geo' => [
            '@type' => 'GeoCoordinates',
            'latitude' => 34.0195,
            'longitude' => -118.4912,
        ],
        'telephone' => preg_replace('/[^+\d\-() ]/', '', $contact['phone']),
        'email' => $contact['email'],
        'founder' => ['@id' => home_url('/#person')],
        'areaServed' => [
            [
                '@type' => 'City',
                'name' => 'Los Angeles',
            ],
            [
                '@type' => 'City',
                'name' => 'Santa Monica',
            ],
            [
                '@type' => 'State',
                'name' => 'California',
            ],
            [
                '@type' => 'Country',
                'name' => 'United States',
            ],
        ],
        'knowsAbout' => [
            'Modern and Contemporary Fine Art',
            'Art Advisory',
            'Art Acquisitions and Deaccessions',
            'Fine Art Appraisals',
            'USPAP-Compliant Appraisals',
            'Collection Management',
            'Collection Strategy',
            'Charitable Art Donations',
            'Estate Art Appraisals',
            'Provenance Research',
        ],
        'hasOfferCatalog' => [
            '@type' => 'OfferCatalog',
            'name' => 'Art Advisory Services',
            'itemListElement' => array_map(fn ($service) => [
                '@type' => 'Offer',
                'itemOffered' => [
                    '@type' => 'Service',
                    'name' => $service['name'],
                ],
            ], get_service_data()),
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
            'description' => 'With over three decades of experience in the art market, Cecilia Dan provides expert guidance to collectors, family offices, institutions, and estates navigating acquisitions, deaccessions, valuations, and long-term collection strategy in Los Angeles and nationally.',
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
            'hasCredential' => [
                [
                    '@type' => 'EducationalOccupationalCredential',
                    'credentialCategory' => 'degree',
                    'name' => 'BA in Art History (cum laude)',
                ],
                [
                    '@type' => 'EducationalOccupationalCredential',
                    'credentialCategory' => 'degree',
                    'name' => 'MBA in Arts Management',
                ],
                [
                    '@type' => 'EducationalOccupationalCredential',
                    'credentialCategory' => 'certificate',
                    'name' => 'Certificate in Appraisal Studies (UCI)',
                ],
                [
                    '@type' => 'EducationalOccupationalCredential',
                    'credentialCategory' => 'professional',
                    'name' => 'Accredited Senior Appraiser (ASA)',
                ],
            ],
            'knowsAbout' => array_merge([
                'Modern and Contemporary Fine Art',
                'Art Advisory',
                'Art Acquisitions and Deaccessions',
                'Fine Art Appraisals',
                'USPAP Standards',
                'Collection Strategy',
                'Collection Management',
                'Charitable Art Donations',
                'Estate Planning and Appraisals',
                'Provenance Research',
                'Los Angeles Art Market',
            ], get_artist_names()),
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
                [
                    '@type' => 'Organization',
                    'name' => 'Hammer Museum',
                    'description' => 'Hammer Circle',
                ],
                [
                    '@type' => 'Organization',
                    'name' => 'Los Angeles County Museum of Art',
                    'alternateName' => 'LACMA',
                    'description' => 'Patron',
                ],
                [
                    '@type' => 'Organization',
                    'name' => 'Museum of Contemporary Art, Los Angeles',
                    'alternateName' => 'MOCA',
                    'description' => "Director's Forum",
                ],
            ],
            'affiliation' => get_institution_affiliations(),
            'sameAs' => [
                'https://www.instagram.com/ceciliadan/',
                'https://www.linkedin.com/in/cecilia-dan-73a32b4/',
                'https://www.artadvisors.org/art-advisor-directory/p/cecilia-dan',
                'https://x.com/carbonmesa',
            ],
            'image' => get_theme_file_uri('resources/images/cecilia-dan.jpg'),
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
                'inLanguage' => 'en-US',
                'dateCreated' => get_the_date('c', get_option('page_on_front')),
                'dateModified' => get_the_modified_date('c', get_option('page_on_front')),
            ];
        }
    }

    // Service schema on services page — read from ACF
    if (is_page('services')) {
        foreach (get_service_data() as $service) {
            $schema[] = [
                '@type' => 'Service',
                'name' => $service['name'],
                'description' => $service['description'],
                'provider' => ['@id' => home_url('/#organization')],
                'areaServed' => [
                    [
                        '@type' => 'City',
                        'name' => 'Los Angeles',
                    ],
                    [
                        '@type' => 'State',
                        'name' => 'California',
                    ],
                    [
                        '@type' => 'Country',
                        'name' => 'United States',
                    ],
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
    if (is_singular()) {
        $post = get_post();

        if (! $post) {
            return '';
        }

        // ACF field takes priority
        if (function_exists('get_field')) {
            $custom = get_field('meta_description', $post->ID);

            if ($custom) {
                return wp_strip_all_tags($custom);
            }
        }

        // Front page falls back to WP tagline
        if (is_front_page()) {
            return get_bloginfo('description', 'display');
        }

        // Other pages fall back to excerpt or trimmed content
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
        $og_path = get_theme_file_path('og-image.jpg');

        if (file_exists($og_path)) {
            return [
                'url' => get_theme_file_uri('og-image.jpg'),
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
 */
function is_noindex_page(): bool
{
    if (! is_singular('page')) {
        return false;
    }

    $noindex_slugs = ['site-notice', 'privacy-policy', 'terms-of-service', 'legal'];
    $post = get_post();

    return $post && in_array($post->post_name, $noindex_slugs, true);
}

/**
 * Get reference data from the About page's list block.
 *
 * Parses the first list block's groups from ACF flexible content.
 * Returns institutions as Schema.org Organization entries and
 * artist names as plain strings. Cached via static variable.
 */
function get_reference_data(): array
{
    static $cache = null;

    if ($cache !== null) {
        return $cache;
    }

    $cache = ['affiliations' => [], 'artists' => []];
    $about = get_page_by_path('about');

    if (! $about || ! have_rows('content', $about->ID)) {
        return $cache;
    }

    while (have_rows('content', $about->ID)) {
        the_row();

        if (get_row_layout() !== 'list_block' || ! have_rows('groups')) {
            continue;
        }

        $groupIndex = 0;

        while (have_rows('groups')) {
            the_row();
            $groupIndex++;

            if (! have_rows('items')) {
                continue;
            }

            while (have_rows('items')) {
                the_row();
                $name = wp_strip_all_tags(get_sub_field('name'));

                if (! $name) {
                    continue;
                }

                if ($groupIndex === 1) {
                    $cache['affiliations'][] = [
                        '@type' => 'Organization',
                        'name' => $name,
                    ];
                } else {
                    $cache['artists'][] = $name;
                }
            }
        }

        // Only use the first list block
        break;
    }

    return $cache;
}

function get_institution_affiliations(): array
{
    return get_reference_data()['affiliations'];
}

function get_artist_names(): array
{
    return get_reference_data()['artists'];
}

/**
 * Get service data from the Services page's accordion block.
 *
 * Reads service headlines and leads from ACF flexible content.
 * Cached via static variable.
 */
function get_service_data(): array
{
    static $cache = null;

    if ($cache !== null) {
        return $cache;
    }

    $cache = [];
    $services = get_page_by_path('services');

    if (! $services || ! have_rows('content', $services->ID)) {
        return $cache;
    }

    while (have_rows('content', $services->ID)) {
        the_row();

        if (get_row_layout() !== 'accordion_block' || ! have_rows('items')) {
            continue;
        }

        while (have_rows('items')) {
            the_row();
            $name = get_sub_field('headline');
            $lead = get_sub_field('lead');

            if ($name) {
                $cache[] = [
                    'name' => wp_strip_all_tags($name),
                    'description' => wp_strip_all_tags($lead),
                ];
            }
        }

        // Only use the first accordion block
        break;
    }

    return $cache;
}
