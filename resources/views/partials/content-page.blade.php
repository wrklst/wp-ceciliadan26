@php(the_content())

@if ($pagination())
  <nav class="page-nav" aria-label="{{ __('Page navigation', 'sage') }}">
    {!! $pagination !!}
  </nav>
@endif
