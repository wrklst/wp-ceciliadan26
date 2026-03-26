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

  @if (have_rows('members'))
    @while (have_rows('members'))
      @php
        the_row();
        $name = get_sub_field('name');
        $position = get_sub_field('position');
        $email = get_sub_field('email');
        $lead = get_sub_field('lead');
        $copy = get_sub_field('copy');
      @endphp

      <details class="border-t last:border-b">
        <summary class="my-4 text-[1.5rem]">
          <h3>{{ $name }}</h3>
        </summary>

        @if ($position)
          <p>{{ $position }}</p>
        @endif

        @if ($email)
          <p>
            <a href="mailto:{!! antispambot($email) !!}">
              {!! antispambot($email) !!}
            </a>
          </p>
        @endif

        @if ($lead)
          <div class="prose mt-4 font-semibold [&>p]:mb-0">
            {!! $lead !!}
          </div>
        @endif

        @if ($copy)
          <div class="prose mt-4 mb-6">
            {!! $copy !!}
          </div>
        @endif
      </details>
    @endwhile
  @endif
</section>
