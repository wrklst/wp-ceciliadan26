@php
  $contact = get_field('contact_details', 'option');
  $profiles = get_field('profiles', 'option');
  $text = get_field('text_snippets', 'option');
@endphp

<footer class="border-t mb-4">
  <div class="mt-4 mb-8 sm:flex sm:justify-between sm:items-start">
    <address id="contact" class="grid justify-items-start">
      <span>{{ $contact['business_name'] }}</span>
      <a href="https://maps.apple.com/?q={{ urlencode($contact['address']) }}" target="_blank" rel="noopener" aria-label="{{ $contact['address'] }} {{ __('(opens in new tab)', 'sage') }}">
        {{ $contact['address'] }}
      </a>
      <a href="mailto:{!! antispambot($contact['email']) !!}">
        {!! antispambot($contact['email']) !!}
      </a>
      <a href="tel:{{ preg_replace('/[^+\d]/', '', $contact['phone']) }}">
        {{ $contact['phone'] }}
      </a>
    </address>

    <div class="flex items-center gap-4 my-8 sm:my-0">
      <a class="hover:opacity-70" href="{{ esc_url($profiles['apaa']) }}" target="_blank" rel="noopener" aria-label="{{ __('Association of Professional Art Advisors (opens in new tab)', 'sage') }}">
        <img class="h-11 w-auto" src="{{ Vite::asset('resources/images/apaa-logo.svg') }}" alt="APAA" width="80" height="44" loading="lazy" decoding="async">
      </a>
      <a class="hover:opacity-70" href="{{ esc_url($profiles['asa']) }}" target="_blank" rel="noopener" aria-label="{{ __('American Society of Appraisers (opens in new tab)', 'sage') }}">
        <img class="h-11 w-auto" src="{{ Vite::asset('resources/images/asa-logo.svg') }}" alt="ASA" width="115" height="44" loading="lazy" decoding="async">
      </a>
    </div>
  </div>

  @if (has_nav_menu('footer_navigation'))
    <nav class="mt-12 mb-4 font-sans text-[0.75rem]" aria-label="{{ wp_get_nav_menu_name('footer_navigation') }}">
      {!! wp_nav_menu([
        'theme_location' => 'footer_navigation',
        'menu_class' => 'footer-menu',
        'echo' => false,
        'depth' => 2,
      ]) !!}
    </nav>
  @endif

  <div class="mt-4 font-sans text-[0.75rem]">
    <p>{{ $text['tagline'] }}</p>
    <p class="mb-4">{{ $text['disclaimer'] }}</p>
    <p class="font-semibold">&copy; {{ date('Y') }} {{ $contact['business_name'] }}. All rights reserved.</p>
  </div>
</footer>
