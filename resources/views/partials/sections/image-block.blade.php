@php
  $imageId = get_sub_field('image');
  $caption = get_sub_field('caption');
  $credit = get_sub_field('photo_credit');
  $creditLink = get_sub_field('photo_credit_link');
@endphp

<section class="my-16">
  @if ($caption)
    <h2 class="sr-only">{{ $caption }}</h2>
  @endif

  <figure class="mx-auto max-w-[50rem]">
    {!! wp_get_attachment_image($imageId, 'content-large', false, [
      'sizes' => '(max-width: 50rem) calc(100vw - clamp(2.5rem, 10vw, 5rem)), 50rem',
      'loading' => 'eager',
      'decoding' => 'auto',
      'fetchpriority' => 'high',
    ]) !!}

    @if ($caption)
      <figcaption class="mt-2 font-sans text-[0.75rem] sm:flex sm:justify-between">
        <span class="font-semibold">{{ $caption }}</span>
        @if ($creditLink)
          <a href="{{ esc_url($creditLink) }}" target="_blank" rel="noopener">
            Photo: {{ $credit }} <span class="sr-only">{{ __('(opens in new tab)', 'sage') }}</span>
          </a>
        @else
          <span>Photo: {{ $credit }}</span>
        @endif
      </figcaption>
    @endif
  </figure>
</section>
