@php
  $hash = get_sub_field('hash');
  $headline = get_sub_field('headline');
  $visible = get_sub_field('headline_visible');
@endphp

<section class="my-32" @if ($hash) id="{{ $hash }}" @endif>
  <h2 class="{{ $visible ? 'mb-2 font-semibold' : 'sr-only' }}">
    {{ $headline }}
  </h2>

  @while (have_rows('groups'))
    @php
      the_row();
      $groupHeadline = get_sub_field('headline');
    @endphp

    <ul class="reference-list small" aria-label="{{ $groupHeadline }}">
      <li class="font-semibold" role="presentation">{{ $groupHeadline }}:</li>
      @while (have_rows('items'))
        @php
          the_row();
          $name = get_sub_field('name');
          $location = get_sub_field('location');
          $url = get_sub_field('url');
        @endphp

        {{-- Single line to prevent whitespace before CSS-generated commas --}}
        <li><?php if ($url): ?><a href="{{ esc_url($url) }}" target="_blank" rel="noopener"><span class="sr-only">{{ __('(opens in new tab)', 'sage') }} </span><span class="sm:whitespace-nowrap">{{ $name }}</span></a><?php else: ?><span class="sm:whitespace-nowrap">{{ $name }}</span><?php endif; ?><?php if ($location): ?><span class="sr-only sm:not-sr-only sm:inline sm:whitespace-nowrap"> ({{ $location }})</span><?php endif; ?></li>
      @endwhile
    </ul>
  @endwhile
</section>
