@php
  $hash = get_sub_field('hash');
  $headline = get_sub_field('headline');
  $visible = get_sub_field('headline_visible');
  $text = get_sub_field('text');
  $textVisible = get_sub_field('text_visible');
@endphp

<section class="my-16" @if ($hash) id="{{ $hash }}" @endif>
  @if ($headline)
    <h2 class="{{ $visible ? 'mb-2 text-[1.125rem]' : 'sr-only' }}">
      {{ $headline }}
    </h2>
  @endif

  @if ($text)
    <div @unless ($textVisible) class="sr-only" @endunless>
      {!! $text !!}
    </div>
  @endif

  @if (have_rows('groups'))
    @while (have_rows('groups'))
      @php
        the_row();
        $groupHeadline = get_sub_field('headline');
      @endphp

      @if ($groupHeadline)
        <h3 class="mt-4 text-[1.125rem]">{{ $groupHeadline }}</h3>
      @endif

      @if (have_rows('items'))
        <ul>
          @while (have_rows('items'))
            @php
              the_row();
              $name = get_sub_field('name');
              $url = get_sub_field('url');
            @endphp

            <li>
              @if ($url)
                <a href="{{ esc_url($url) }}" target="_blank" rel="noopener">
                  {{ $name }} <span class="sr-only">{{ __('(opens in new tab)', 'sage') }}</span>
                </a>
              @else
                {{ $name }}
              @endif
            </li>
          @endwhile
        </ul>
      @endif
    @endwhile
  @endif
</section>
