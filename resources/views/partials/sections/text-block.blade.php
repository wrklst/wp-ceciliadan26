@php
  $hash = get_sub_field('hash');
  $headline = get_sub_field('headline');
  $visible = get_sub_field('headline_visible');
  $copy = get_sub_field('copy');
  $headingId = ($hash ?: 'text') . '-heading';
@endphp

<section class="my-16" @if ($hash) id="{{ $hash }}" @endif aria-labelledby="{{ $headingId }}">
  @if ($headline)
    <h2 id="{{ $headingId }}" class="{{ $visible ? 'mb-2 text-[1.125rem]' : 'sr-only' }}">
      {{ $headline }}
    </h2>
  @endif

  @if ($copy)
    <div class="prose">
      {!! $copy !!}
    </div>
  @endif
</section>
