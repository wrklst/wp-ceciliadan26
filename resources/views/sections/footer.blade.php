@php
  $contact = get_field('contact_details', 'option');
  $profiles = get_field('profiles', 'option');
  $text = get_field('text_snippets', 'option');
  $businessName = $contact['business_name'];
  $address = $contact['address'];
  $emailHref = antispambot($contact['email'], 1);
  $emailText = antispambot($contact['email']);
  $phone = $contact['phone'];
  $phoneTel = preg_replace('/[^+\d]/', '', $phone);
  $apaaUrl = $profiles['apaa'] ?? null;
  $asaUrl = $profiles['asa'] ?? null;
  $cta = $text['call-to-action'] ?? null;
  $tagline = $text['tagline'] ?? null;
  $disclaimer = $text['disclaimer'] ?? null;
  $now = new DateTime('now', new DateTimeZone('America/Los_Angeles'));
@endphp

<footer class="mb-6 border-t" aria-label="{{ __('Site footer', 'sage') }}">
  <div class="mt-4 mb-16 md:flex md:items-start md:justify-between">
    <div>
      <address id="contact" class="flex flex-col items-start" aria-label="{{ __('Contact information', 'sage') }}">
        <span class="font-semibold">{{ $businessName }}</span>
        <a href="https://maps.apple.com/?q={{ urlencode($address) }}" target="_blank" rel="noopener">
          {{ $address }} <span class="sr-only">{{ __('(opens in new tab)', 'sage') }}</span>
        </a>
        <a href="mailto:{!! $emailHref !!}">
          {!! $emailText !!} <span class="sr-only">{{ __('(opens email client)', 'sage') }}</span>
        </a>
        <a href="tel:{{ $phoneTel }}" aria-label="{{ __('Call', 'sage') }} {{ $phone }}">
          {{ $phone }}
        </a>
      </address>
      @if ($cta)
        <div class="mt-4 min-h-11">
          <p>
            <a href="{{ esc_url($cta['link']['url']) }}">{{ $cta['link']['title'] }} <span class="sr-only">{{ __('(opens email client)', 'sage') }}</span></a>
            {{ $cta['text_after'] }}
          </p>
          <time id="local-time" datetime="{{ $now->format('c') }}" class="small" aria-label="{{ __('Local business time', 'sage') }}">Local time at business: {{ $now->format('l, g:i A T') }}</time>
        </div>
      @endif
    </div>

    <div class="mt-12 flex items-start gap-6 md:mt-0.5">
      @if ($apaaUrl)
        <a href="{{ esc_url($apaaUrl) }}" target="_blank" rel="noopener me" aria-label="{{ __('Association of Professional Art Advisors (opens in new tab)', 'sage') }}" class="hover:opacity-70">
          <img src="{{ Vite::asset('resources/images/apaa-logo.svg') }}" alt="APAA member" width="80" height="44" class="h-11 w-auto" loading="lazy" decoding="async">
        </a>
      @endif
      @if ($asaUrl)
        <a href="{{ esc_url($asaUrl) }}" target="_blank" rel="noopener me" aria-label="{{ __('American Society of Appraisers (opens in new tab)', 'sage') }}" class="hover:opacity-70">
          <img src="{{ Vite::asset('resources/images/asa-logo.svg') }}" alt="American Society of Appraisers" width="115" height="44" class="h-11 w-auto" loading="lazy" decoding="async">
        </a>
      @endif
    </div>
  </div>

  <nav class="mt-16 mb-8 small" aria-label="{{ wp_get_nav_menu_name('footer_navigation') }}">
    {!! wp_nav_menu([
      'theme_location' => 'footer_navigation',
      'menu_class' => 'footer-menu',
      'container' => false,
      'echo' => false,
      'depth' => 2,
    ]) !!}
  </nav>

  @if ($tagline || $disclaimer)
    <aside class="my-4 small" aria-label="{{ __('About this firm', 'sage') }}">
      @if ($tagline)
        <p>{{ $tagline }}</p>
      @endif
      @if ($disclaimer)
        <p>{{ $disclaimer }}</p>
      @endif
    </aside>
  @endif

  <p class="small font-semibold">&copy; {{ $now->format('Y') }} {{ $businessName }}. All rights reserved.</p>
</footer>