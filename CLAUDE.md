# Cecilia Dan Fine Art — Theme

Website for Cecilia Dan Fine Art, an art advisory and appraisal firm based in Santa Monica, Los Angeles. Built as a custom WordPress theme on the Roots stack.

## Tech Stack

- **WordPress** 6.9.4 (standard install, not Bedrock)
- **Sage** 11.0.1 — hybrid WordPress starter theme
- **Acorn** 5.1.1 — Laravel framework bridge for WordPress
- **PHP** 8.4 / **Node** 22
- **Vite** 7.3 + **Tailwind CSS** 4.2 — frontend build pipeline
- **Laravel Blade** — templating engine
- **ACF Pro** — custom fields (plugin installed)
- **Classic Editor** — plugin installed
- **Laravel Herd** — local development environment

## Project Structure

```
ceciliadan/                     ← WP root (NOT a git repo)
├── wp-config.php               ← Hardened (debug on, salts set, file edit disabled)
├── .htaccess                   ← Copy from theme's .htaccess template
├── robots.txt                  ← Copy from theme's robots.txt template
├── wp-content/
│   ├── plugins/
│   │   ├── advanced-custom-fields-pro/
│   │   └── classic-editor/
│   ├── themes/
│   │   └── cecilia-dan-fine-art-theme/  ← Theme directory (git repo root)
│   │       ├── app/
│   │       │   ├── setup.php           ← Theme setup, wp_head cleanup, bloat removal
│   │       │   ├── filters.php         ← Security filters, image pipeline, SEO title
│   │       │   ├── seo.php             ← Meta description, OG tags, JSON-LD
│   │       │   ├── Providers/
│   │       │   │   └── ThemeServiceProvider.php
│   │       │   └── View/Composers/
│   │       │       ├── App.php         ← Global: $siteName
│   │       │       └── Post.php        ← Post views: $title, $pagination
│   │       ├── resources/
│   │       │   ├── css/
│   │       │   │   ├── app.css         ← Tailwind 4 entry + focus, print, motion, scrollbar
│   │       │   │   └── editor.css      ← Block editor styles
│   │       │   ├── js/
│   │       │   │   ├── app.js          ← Frontend entry (image/font glob)
│   │       │   │   └── editor.js       ← Block editor scripts
│   │       │   ├── fonts/              ← Self-hosted web fonts (empty)
│   │       │   ├── images/             ← Theme images + og-image.jpg (1200x630)
│   │       │   └── views/
│   │       │       ├── layouts/app.blade.php       ← HTML shell (color-scheme, format-detection)
│   │       │       ├── sections/
│   │       │       │   ├── header.blade.php
│   │       │       │   └── footer.blade.php
│   │       │       ├── partials/
│   │       │       │   ├── content.blade.php
│   │       │       │   ├── content-page.blade.php
│   │       │       │   ├── content-single.blade.php
│   │       │       │   ├── content-search.blade.php
│   │       │       │   ├── entry-meta.blade.php
│   │       │       │   └── page-header.blade.php
│   │       │       ├── components/
│   │       │       │   └── alert.blade.php
│   │       │       ├── forms/
│   │       │       │   └── search.blade.php
│   │       │       ├── index.blade.php
│   │       │       ├── page.blade.php
│   │       │       ├── single.blade.php
│   │       │       ├── 404.blade.php
│   │       │       ├── search.blade.php
│   │       │       └── template-custom.blade.php
│   │       ├── .htaccess               ← Template — copy to WP root
│   │       ├── robots.txt              ← Template — copy to WP root
│   │       ├── public/build/           ← Vite build output (gitignored)
│   │       ├── vendor/                 ← Composer dependencies (gitignored)
│   │       ├── node_modules/           ← npm dependencies (gitignored)
│   │       ├── composer.json
│   │       ├── package.json
│   │       ├── vite.config.js
│   │       ├── theme.json              ← Source theme.json (preprocessed by Vite)
│   │       ├── functions.php           ← Boots Acorn + loads setup/filters/seo
│   │       └── style.css               ← WP theme header only
│   ├── languages/                      ← de_DE translations (dev locale only)
│   └── uploads/
```

## Architecture

### PHP Files (app/)

- **setup.php** — `after_setup_theme` (nav menus, theme support, block editor injection), `init` (wp_head cleanup: generator, RSD, WLW, shortlink, feeds, emoji, global styles, SVG filters), `wp_enqueue_scripts` (dequeue block CSS, deregister speculation rules)
- **filters.php** — Security (XML-RPC disable, REST API auth restriction, user enumeration blocking, oEmbed author stripping), SEO (title separator `–`, front page title cleanup), Image pipeline (disable default sizes, srcset width filtering, per-format quality, big image threshold 3840px), Sitemap (exclude noindex pages)
- **seo.php** — Meta description (ACF field → excerpt → trimmed content fallback), Open Graph (full metadata), Twitter/X cards, noindex for legal pages, JSON-LD structured data (ProfessionalService + Person + ProfilePage + Service schemas with LA/Santa Monica geo targeting)

### CSS Foundation (app.css)

- Tailwind 4 with `theme(static)` and source scanning of views/ and app/
- Scrollbar hiding (cross-browser)
- Focus indicators (WCAG 2.4.13: `outline: 2px solid currentColor; outline-offset: 2px`)
- Reduced motion (`prefers-reduced-motion: reduce`)
- Print styles (block layout, hide nav/footer, show link URLs, prevent page breaks)

### Security Layers

- **Server**: .htaccess blocks XML-RPC, sets security headers (X-Content-Type-Options, X-Frame-Options, Referrer-Policy, COOP, Permissions-Policy), compression, caching
- **Application**: XML-RPC disabled via filter, REST API restricted to authenticated users (with public route allowlist), /wp/v2/users endpoint removed, author archives redirect to home, oEmbed author data stripped
- **wp-config.php**: File editing disabled, revisions capped, debug logging to file only

### Accessibility

- **Target**: WCAG 2.2 AAA (exceeds California WCAG 2.2 AA mandate)
- **Legal basis**: Unruh Civil Rights Act (Civil Code § 51), ADA Title III, Gov. Code §§ 7405, 11135, 11546.7
- **Key checks**: 24x24px touch targets (SC 2.5.8), focus not obscured (SC 2.4.11), no CAPTCHA without alternative (SC 3.3.8)

## Conventions

- **Indent**: 2 spaces for Blade/CSS/JS, 4 spaces for PHP (per `.editorconfig`)
- **Text domain**: `sage`
- **Site language**: English (US) — dev environment uses de_DE locale
- **CSS**: Tailwind 4 utility-first, `@import "tailwindcss" theme(static)` in `app.css`
- **Views**: Blade templates in `resources/views/`, auto-discovered by Acorn
- **Composers**: View composers in `app/View/Composers/`, extend `Roots\Acorn\View\Composer`
- **Assets**: Vite handles CSS/JS/fonts/images, base path `wp-content/themes/cecilia-dan-fine-art-theme/public/build/`
- **SEO**: No plugin — meta/OG/JSON-LD handled in `app/seo.php`. ACF `meta_description` field supported for per-page descriptions.
- **Image quality**: AVIF 68, WebP 80, JPEG 82. Big image threshold 3840px for portfolio work.
- **Noindex pages**: Slugs `privacy-policy`, `terms-of-service`, `legal` are noindexed and excluded from sitemap.

## Build Commands

```bash
npm run dev     # Vite dev server with HMR
npm run build   # Production build to public/build/
```

## Deployment Checklist

- [ ] Copy `.htaccess` from theme to WP root — uncomment HSTS header
- [ ] Copy `robots.txt` from theme to WP root — uncomment Sitemap line, set production domain
- [ ] Add `og-image.jpg` (1200x630, JPEG quality 80-85) to `resources/images/`
- [ ] Add favicons: `favicon.ico` (32x32), `favicon.svg` (dark mode), `apple-touch-icon.png` (180x180)
- [ ] Set `WP_DEBUG` to `false`, `WP_DEBUG_LOG` to `false` in production wp-config
- [ ] Add `FORCE_SSL_ADMIN` to production wp-config
- [ ] Add `define('DISABLE_WP_CRON', true)` and set up system crontab
- [ ] Install and configure Modern Image Formats plugin for AVIF/WebP
- [ ] Self-host fonts: add WOFF2 files to `resources/fonts/`, add `@font-face` declarations with preload
- [ ] Create Google Business Profile for Cecilia Dan Fine Art
- [ ] Verify all pages with Google Rich Results Test and opengraph.xyz
