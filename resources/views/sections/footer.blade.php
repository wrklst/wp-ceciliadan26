<footer class="content-info">
  {{-- a. Contact details --}}
  <div class="footer-contact">
    @if ($businessName = get_field('footer_business_name', 'option'))
      <p class="footer-business-name">{{ $businessName }}</p>
    @endif

    @if ($address = get_field('footer_address', 'option'))
      <address>{!! $address !!}</address>
    @endif

    @if ($footerEmail = get_field('footer_email', 'option'))
      <a href="mailto:{{ antispambot($footerEmail) }}">
        {{ antispambot($footerEmail) }}
      </a>
    @endif

    @if ($footerContactLink = get_field('footer_contact_link', 'option'))
      <a href="{{ esc_url($footerContactLink) }}">
        {{ __('Contact Form', 'sage') }}
      </a>
    @endif
  </div>

  {{-- b. Tagline --}}
  @if ($footerTagline = get_field('footer_tagline', 'option'))
    <p class="footer-tagline">{{ $footerTagline }}</p>
  @endif

  {{-- c. Disclaimer --}}
  @if ($footerDisclaimer = get_field('footer_disclaimer', 'option'))
    <p class="footer-disclaimer">{{ $footerDisclaimer }}</p>
  @endif

  {{-- d. Membership logos --}}
  <div class="footer-memberships">
    @if ($apaaUrl = get_field('footer_apaa_url', 'option'))
      <a href="{{ esc_url($apaaUrl) }}" target="_blank" rel="noopener" aria-label="{{ __('Association of Professional Art Advisors (opens in new tab)', 'sage') }}">
        {{-- APAA logo: add SVG or img to resources/images/apaa-logo.svg --}}
        @if (file_exists(get_theme_file_path('resources/images/apaa-logo.svg')))
          <img src="{{ Vite::asset('resources/images/apaa-logo.svg') }}" alt="APAA" loading="lazy" decoding="async">
        @else
          <span>APAA</span>
        @endif
      </a>
    @endif

    @if ($asaUrl = get_field('footer_asa_url', 'option'))
      <a href="{{ esc_url($asaUrl) }}" target="_blank" rel="noopener" aria-label="{{ __('American Society of Appraisers (opens in new tab)', 'sage') }}">
        {{-- ASA logo: add SVG or img to resources/images/asa-logo.svg --}}
        @if (file_exists(get_theme_file_path('resources/images/asa-logo.svg')))
          <img src="{{ Vite::asset('resources/images/asa-logo.svg') }}" alt="ASA" loading="lazy" decoding="async">
        @else
          <span>ASA</span>
        @endif
      </a>
    @endif
  </div>

  {{-- e. Sitemap navigation --}}
  @if (has_nav_menu('footer_navigation'))
    <nav class="footer-nav" aria-label="{{ __('Sitemap', 'sage') }}">
      {!! wp_nav_menu([
        'theme_location' => 'footer_navigation',
        'menu_class' => 'footer-menu',
        'echo' => false,
        'depth' => 1,
      ]) !!}
    </nav>
  @endif

  {{-- f. Legal links --}}
  @if (has_nav_menu('legal_navigation'))
    <nav class="footer-legal" aria-label="{{ __('Legal', 'sage') }}">
      {!! wp_nav_menu([
        'theme_location' => 'legal_navigation',
        'menu_class' => 'legal-menu',
        'echo' => false,
        'depth' => 1,
      ]) !!}
    </nav>
  @endif

  {{-- g. Copyright --}}
  @if ($copyright = get_field('footer_copyright', 'option'))
    <p class="footer-copyright">
      <small>&copy; {{ str_replace('{year}', date('Y'), $copyright) }}</small>
    </p>
  @endif
</footer>
