@php
  $hash = get_sub_field('hash');
  $headline = get_sub_field('headline');
  $visible = get_sub_field('headline_visible');
  $copy = get_sub_field('copy');
@endphp

<section class="my-16" @if ($hash) id="{{ $hash }}" @endif>
  @if ($headline)
    <h2 class="{{ $visible ? 'mb-2 text-[1.125rem]' : 'sr-only' }}">
      {{ $headline }}
    </h2>
  @endif

  @if ($copy)
    <div class="prose">
      {!! $copy !!}
    </div>
  @endif
</section>
