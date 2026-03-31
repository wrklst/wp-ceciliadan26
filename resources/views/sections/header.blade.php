<header aria-label="{{ __('Site header', 'sage') }}">
  @if (is_front_page())
    <h1><a href="{{ home_url('/') }}">{!! $siteName !!}</a></h1>
  @else
    <a href="{{ home_url('/') }}">{!! $siteName !!}</a>
  @endif

  @if (has_nav_menu('primary_navigation'))
    <nav aria-label="{{ wp_get_nav_menu_name('primary_navigation') }}">
      {!! wp_nav_menu(['theme_location' => 'primary_navigation', 'echo' => false]) !!}
    </nav>
  @endif
</header>
