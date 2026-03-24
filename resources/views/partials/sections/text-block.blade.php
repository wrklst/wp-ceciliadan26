<section>
  @if (get_sub_field('headline'))
    <h2 class="{{ get_sub_field('headline_visible') ? 'text-lg' : 'sr-only' }}">
      {{ get_sub_field('headline') }}
    </h2>
  @endif

  @if (get_sub_field('copy'))
    <div class="prose">
      {!! get_sub_field('copy') !!}
    </div>
  @endif
</section>
