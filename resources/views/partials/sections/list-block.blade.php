<section @if (get_sub_field('hash')) id="{{ get_sub_field('hash') }}" @endif>
  @if (get_sub_field('headline'))
    <h2 class="{{ get_sub_field('headline_visible') ? 'text-[1.125rem]' : 'sr-only' }}">
      {{ get_sub_field('headline') }}
    </h2>
  @endif

  @if (get_sub_field('text'))
    <div @unless (get_sub_field('text_visible')) class="sr-only" @endunless>
      {!! get_sub_field('text') !!}
    </div>
  @endif

  @if (have_rows('groups'))
    @while (have_rows('groups')) @php(the_row())
      @if (get_sub_field('headline'))
        <h3>{{ get_sub_field('headline') }}</h3>
      @endif

      @if (have_rows('items'))
        <ul>
          @while (have_rows('items')) @php(the_row())
            <li>
              @if (get_sub_field('url'))
                <a href="{{ esc_url(get_sub_field('url')) }}" target="_blank" rel="noopener" aria-label="{{ get_sub_field('name') }} ({{ __('opens in new tab', 'sage') }})">
                  {{ get_sub_field('name') }}
                </a>
              @else
                {{ get_sub_field('name') }}
              @endif
            </li>
          @endwhile
        </ul>
      @endif
    @endwhile
  @endif
</section>
