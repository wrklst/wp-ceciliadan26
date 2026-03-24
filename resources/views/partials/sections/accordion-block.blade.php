<section>
  @if (get_sub_field('headline'))
    <h2 @unless (get_sub_field('headline_visible')) class="sr-only" @endunless>
      {{ get_sub_field('headline') }}
    </h2>
  @endif

  @if (have_rows('items'))
    <div class="accordion">
      @while (have_rows('items')) @php(the_row())
        <details @if (get_sub_field('hash')) id="{{ get_sub_field('hash') }}" @endif>
          <summary>
            <h3>{{ get_sub_field('headline') }}</h3>
          </summary>

          <div class="accordion-content">
            @if (get_sub_field('lead_text'))
              <p class="lead">
                {!! get_sub_field('lead_text') !!}
              </p>
            @endif

            @if (get_sub_field('copy_text'))
              <div class="prose">
                {!! get_sub_field('copy_text') !!}
              </div>
            @endif
          </div>
        </details>
      @endwhile
    </div>
  @endif
</section>
