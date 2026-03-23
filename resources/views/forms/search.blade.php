<form role="search" method="get" class="search-form" action="{{ home_url('/') }}">
  <label for="search-input">
    <span class="sr-only">
      {{ _x('Search for:', 'label', 'sage') }}
    </span>

    <input
      id="search-input"
      type="search"
      placeholder="{!! esc_attr_x('Search &hellip;', 'placeholder', 'sage') !!}"
      value="{{ get_search_query() }}"
      name="s"
    >
  </label>

  <button type="submit">{{ _x('Search', 'submit button', 'sage') }}</button>
</form>
