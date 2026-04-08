@php
  $hash = get_sub_field('hash');
  $headline = get_sub_field('headline');
  $visible = get_sub_field('headline_visible');
  $cta = get_sub_field('call-to-action');
  $headingId = ($hash ?: 'accordion') . '-heading';
@endphp

<section class="my-16" @if ($hash) id="{{ $hash }}" @endif aria-labelledby="{{ $headingId }}">
  <h2 id="{{ $headingId }}" class="{{ $visible ? 'mb-2 font-semibold' : 'sr-only' }}">
    {{ $headline }}
  </h2>

  @while (have_rows('items'))
    @php
      the_row();
      $itemHash = get_sub_field('hash');
      $itemHeadline = get_sub_field('headline');
      $lead = get_sub_field('lead');
      $copy = get_sub_field('copy');
    @endphp

    <details class="border-t [&:last-of-type]:border-b" @if ($itemHash) id="{{ $itemHash }}" @endif name="accordion-{{ $hash }}">
      <summary class="my-4 text-[1.25rem]">
        <h3>{{ $itemHeadline }}</h3>
      </summary>

      <div class="mb-8 max-w-[50rem]">
        <div class="mb-4 prose font-semibold">
          {!! $lead !!}
        </div>
        <div class="prose font-sans text-[0.9rem]">
          {!! $copy !!}
        </div>
      </div>
    </details>
  @endwhile

  @if ($cta['link'] ?? false)
    <div class="mt-4 small font-semibold">
      <p><?php if ($cta['text_before'] ?? false): ?>{{ $cta['text_before'] }} <?php endif; ?><a href="{{ esc_url($cta['link']['url']) }}">{{ $cta['link']['title'] }} <span class="sr-only">{{ __('(opens email client)', 'sage') }}</span></a></p>
    </div>
  @endif
</section>
