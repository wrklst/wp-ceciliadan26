<?php

declare(strict_types=1);

namespace App\View\Composers;

use Roots\Acorn\View\Composer;

class App extends Composer
{
    /**
     * List of views served by this composer.
     *
     * @var array<int, string>
     */
    protected static $views = [
        '*',
    ];

    /**
     * Retrieve the site name.
     */
    public function siteName(): string
    {
        return get_bloginfo('name', 'display');
    }

    /**
     * Retrieve the CSP nonce for inline scripts.
     */
    public function cspNonce(): string
    {
        return function_exists('App\\get_csp_nonce') ? \App\get_csp_nonce() : '';
    }
}
