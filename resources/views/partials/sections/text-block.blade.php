<section>
  @if (get_sub_field('headline'))
    <h2 @unless (get_sub_field('headline_visible')) class="sr-only" @endunless>
      {{ get_sub_field('headline') }}
    </h2>
  @endif

  @if (get_sub_field('copy'))
    <div class="prose">
      {!! get_sub_field('copy') !!}
    </div>
  @endif
</section>
