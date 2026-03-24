@php($imageId = get_sub_field('image'))

<section>
  @if (get_sub_field('headline'))
    <h2 @unless (get_sub_field('headline_visible')) class="sr-only" @endunless>
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

      @if (get_sub_field('caption'))
        <figcaption>
          {{ get_sub_field('caption') }}
        </figcaption>
      @endif
    </figure>
  @endif
</section>
