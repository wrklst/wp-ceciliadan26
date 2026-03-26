@php
  $hash = get_sub_field('hash');
  $headline = get_sub_field('headline');
  $visible = get_sub_field('headline_visible');
@endphp

<section class="my-16" @if ($hash) id="{{ $hash }}" @endif>
  @if ($headline)
    <h2 class="{{ $visible ? 'mb-2 text-[1.125rem]' : 'sr-only' }}">
      {{ $headline }}
    </h2>
  @endif

  @while (have_rows('items'))
    @php
      the_row();
      $itemHash = get_sub_field('hash');
      $itemHeadline = get_sub_field('headline');
      $lead = get_sub_field('lead');
      $copy = get_sub_field('copy');
      $cta = get_sub_field('call-to-action');
    @endphp

    <details class="border-t last:border-b" id="{{ $itemHash }}" name="accordion-{{ $hash }}">
      <summary class="my-4 text-[1.5rem]">
        <h3>{{ $itemHeadline }}</h3>
      </summary>

      <div class="my-4 md:grid md:grid-cols-[38.2%_61.8%] md:gap-8 max-w-[72rem]">
        <div class="flex flex-col">
          <div class="prose font-semibold [&>p]:mb-0">
            {!! $lead !!}
          </div>
          @if ($cta)
            <div class="mt-auto mt-4 mb-6 font-sans font-semibold text-[0.75rem]">
              {!! $cta !!}
            </div>
          @endif
        </div>
        <div class="prose mt-4 mb-6 md:mt-0">
          {!! $copy !!}
        </div>
      </div>
    </details>
  @endwhile
</section>
