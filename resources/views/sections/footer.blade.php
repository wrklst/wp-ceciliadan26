@php
  $contact = get_field('contact_details', 'option');
  $profiles = get_field('profiles', 'option');
  $text = get_field('text_snippets', 'option');
@endphp

<footer class="max-w-7xl mx-[5vw]">
  {{-- a. Contact details --}}
  <div class="footer-contact">
    @if ($contact['business_name'] ?? false)
      <p id="contact">{{ $contact['business_name'] }}</p>
    @endif

    @if ($contact['address'] ?? false)
      <address>{{ $contact['address'] }}</address>
    @endif

    @if ($contact['email'] ?? false)
      <a href="mailto:{{ antispambot($contact['email']) }}">
        {{ antispambot($contact['email']) }}
      </a>
    @endif

    @if ($contact['phone'] ?? false)
      <a href="tel:{{ preg_replace('/[^+\d]/', '', $contact['phone']) }}">
        {{ $contact['phone'] }}
      </a>
    @endif
  </div>

  {{-- b. Tagline --}}
  @if ($text['tagline'] ?? false)
    <p class="footer-tagline">{{ $text['tagline'] }}</p>
  @endif

  {{-- c. Disclaimer --}}
  @if ($text['disclaimer'] ?? false)
    <p class="footer-disclaimer">{{ $text['disclaimer'] }}</p>
  @endif

  {{-- d. Membership logos --}}
  <div class="footer-memberships">
    @if ($profiles['apaa'] ?? false)
      <a href="{{ esc_url($profiles['apaa']) }}" target="_blank" rel="noopener" aria-label="{{ __('Association of Professional Art Advisors (opens in new tab)', 'sage') }}">
        @if (file_exists(get_theme_file_path('resources/images/apaa-logo.svg')))
          <img src="{{ Vite::asset('resources/images/apaa-logo.svg') }}" alt="APAA" width="120" height="44" loading="lazy" decoding="async">
        @else
          <span>APAA</span>
        @endif
      </a>
    @endif

    @if ($profiles['asa'] ?? false)
      <a href="{{ esc_url($profiles['asa']) }}" target="_blank" rel="noopener" aria-label="{{ __('American Society of Appraisers (opens in new tab)', 'sage') }}">
        @if (file_exists(get_theme_file_path('resources/images/asa-logo.svg')))
          <img src="{{ Vite::asset('resources/images/asa-logo.svg') }}" alt="ASA" width="120" height="44" loading="lazy" decoding="async">
        @else
          <span>ASA</span>
        @endif
      </a>
    @endif
  </div>

  {{-- e. Sitemap navigation --}}
  @if (has_nav_menu('footer_navigation'))
    <nav aria-label="{{ wp_get_nav_menu_name('footer_navigation') }}">
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
    <nav aria-label="{{ wp_get_nav_menu_name('legal_navigation') }}">
      {!! wp_nav_menu([
        'theme_location' => 'legal_navigation',
        'menu_class' => 'legal-menu',
        'echo' => false,
        'depth' => 1,
      ]) !!}
    </nav>
  @endif

  {{-- g. Copyright --}}
  <p class="footer-copyright">
    <small>&copy; {{ date('Y') }} {{ $contact['business_name'] ?? 'Cecilia Dan Fine Art' }}. All rights reserved.</small>
  </p>
</footer>
