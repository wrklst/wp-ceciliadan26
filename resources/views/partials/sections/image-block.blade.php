@php($imageId = get_sub_field('image'))

<section @if (get_sub_field('hash')) id="{{ get_sub_field('hash') }}" @endif>
  @if (get_sub_field('headline'))
    <h2 class="{{ get_sub_field('headline_visible') ? 'text-[1.125rem]' : 'sr-only' }}">
      {{ get_sub_field('headline') }}
    </h2>
  @endif

  @if ($imageId)
    <figure>
      {!! wp_get_attachment_image($imageId, 'content-large', false, [
        'sizes' => '(max-width: 640px) 100vw, (max-width: 1280px) 100vw, 1920px',
        'loading' => 'lazy',
        'decoding' => 'async',
      ]) !!}

      @if (get_sub_field('caption') || get_sub_field('photo_credit'))
        <figcaption>
          @if (get_sub_field('caption'))
            {{ get_sub_field('caption') }}
          @endif
          @if (get_sub_field('photo_credit'))
            <span class="photo-credit">{{ get_sub_field('photo_credit') }}</span>
          @endif
        </figcaption>
      @endif
    </figure>
  @endif
</section>
