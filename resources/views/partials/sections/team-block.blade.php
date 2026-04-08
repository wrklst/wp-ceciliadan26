@php
  $hash = get_sub_field('hash');
  $headline = get_sub_field('headline');
  $visible = get_sub_field('headline_visible');
  $headingId = ($hash ?: 'team') . '-heading';
@endphp

<section class="my-16" @if ($hash) id="{{ $hash }}" @endif aria-labelledby="{{ $headingId }}">
  <h2 id="{{ $headingId }}" class="{{ $visible ? 'mb-2' : 'sr-only' }}">
    {{ $headline }}
  </h2>

  @while (have_rows('members'))
    @php
      the_row();
      $name = get_sub_field('name');
      $email = get_sub_field('email');
      $emailHref = $email ? antispambot($email, 1) : '';
      $emailText = $email ? antispambot($email) : '';
      $lead = get_sub_field('lead');
      $copy = get_sub_field('copy');
    @endphp

    <details class="group border-t [&:last-of-type]:border-b" name="team-{{ $hash }}">
      <summary class="py-4 text-[1.5rem] group-open:sr-only">
        <h3>{{ $name }}</h3>
      </summary>

      <div class="mt-4 mb-8 lg:w-[95%]">
        <div class="mb-6 text-[1.5rem] select-none">
          {!! $lead !!}
        </div>
        <div class="my-6 prose font-sans">
          {!! $copy !!}
        </div>
        @if ($email)
          <p class="mt-6 font-semibold">
            {{ __('Email:', 'sage') }} <a href="mailto:{!! $emailHref !!}">{!! $emailText !!} <span class="sr-only">{{ __('(opens email client)', 'sage') }}</span></a>
          </p>
        @endif
        <button class="team-close sr-only focus:not-sr-only small font-semibold">{{ __('Close text on', 'sage') }} {{ $name }}</button>
      </div>
    </details>
  @endwhile
</section>
