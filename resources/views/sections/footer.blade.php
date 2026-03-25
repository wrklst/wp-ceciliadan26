@php
  $contact = get_field('contact_details', 'option');
  $profiles = get_field('profiles', 'option');
  $text = get_field('text_snippets', 'option');
@endphp

<footer class="mb-6 border-t">
  <div class="mt-4 mb-16 md:flex md:items-start md:justify-between">
    <div>
      <address id="contact" class="flex flex-col items-start">
        <span>{{ $contact['business_name'] }}</span>
        <a href="https://maps.apple.com/?q={{ urlencode($contact['address']) }}" target="_blank" rel="noopener">
          {{ $contact['address'] }} <span class="sr-only">{{ __('(opens in new tab)', 'sage') }}</span>
        </a>
        <a href="mailto:{!! antispambot($contact['email']) !!}">
          {!! antispambot($contact['email']) !!}
        </a>
        <a href="tel:{{ preg_replace('/[^+\d]/', '', $contact['phone']) }}">
          {{ $contact['phone'] }}
        </a>
      </address>
      <div class="mt-4 min-h-11">
        {!! $contact['call-to-action'] !!}
        <time id="local-time" datetime="{{ wp_date('c', null, new DateTimeZone('America/Los_Angeles')) }}" class="font-sans text-[0.75rem]">Local time at business: {{ wp_date('l, g:i A T', null, new DateTimeZone('America/Los_Angeles')) }}</time>
      </div>
    </div>

    <div class="flex items-start gap-6 mt-12 md:mt-0">
      <a href="{{ esc_url($profiles['apaa']) }}" target="_blank" rel="noopener" aria-label="{{ __('Association of Professional Art Advisors (opens in new tab)', 'sage') }}" class="hover:opacity-70">
        <img src="{{ Vite::asset('resources/images/apaa-logo.svg') }}" alt="APAA member" width="80" height="44" class="h-11 w-auto" loading="lazy" decoding="async">
      </a>
      <a href="{{ esc_url($profiles['asa']) }}" target="_blank" rel="noopener" aria-label="{{ __('American Society of Appraisers (opens in new tab)', 'sage') }}" class="hover:opacity-70">
        <img src="{{ Vite::asset('resources/images/asa-logo.svg') }}" alt="American Society of Appraisers" width="115" height="44" class="h-11 w-auto" loading="lazy" decoding="async">
      </a>
    </div>
  </div>

  <nav class="mt-16 mb-8 font-sans text-[0.75rem]" aria-label="{{ wp_get_nav_menu_name('footer_navigation') }}">
    {!! wp_nav_menu([
      'theme_location' => 'footer_navigation',
      'menu_class' => 'footer-menu',
      'echo' => false,
      'depth' => 2,
    ]) !!}
  </nav>

  <div class="my-4 font-sans text-[0.75rem]">
    <p>{{ $text['tagline'] }}</p>
    <p>{{ $text['disclaimer'] }}</p>
  </div>

  <p class="font-sans text-[0.75rem] font-semibold">&copy; {{ wp_date('Y') }} {{ $contact['business_name'] }}. All rights reserved.</p>
</footer>
