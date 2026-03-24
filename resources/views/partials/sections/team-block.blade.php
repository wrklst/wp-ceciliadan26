<section>
  @if (get_sub_field('headline'))
    <h2 @unless (get_sub_field('headline_visible')) class="sr-only" @endunless>
      {{ get_sub_field('headline') }}
    </h2>
  @endif

  @if (have_rows('members'))
    <div class="team">
      @while (have_rows('members')) @php(the_row())
        <details>
          <summary>
            <h3>{{ get_sub_field('name') }}</h3>

            @if (get_sub_field('position'))
              <p class="position">{{ get_sub_field('position') }}</p>
            @endif
          </summary>

          <div class="team-member-content">
            @if (get_sub_field('email'))
              <p>
                <a href="mailto:{{ antispambot(get_sub_field('email')) }}">
                  {{ antispambot(get_sub_field('email')) }}
                </a>
              </p>
            @endif

            @if (get_sub_field('lead'))
              <p class="lead">
                {!! get_sub_field('lead') !!}
              </p>
            @endif

            @if (get_sub_field('copy'))
              <div class="prose">
                {!! get_sub_field('copy') !!}
              </div>
            @endif
          </div>
        </details>
      @endwhile
    </div>
  @endif
</section>
