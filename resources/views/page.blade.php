@extends('layouts.app')

@section('content')
  @while(have_posts()) @php(the_post())
    @if (have_rows('page_content_sections'))
      @while (have_rows('page_content_sections')) @php(the_row())
        @includeFirst(['partials.sections.' . str_replace('_', '-', get_row_layout()), 'partials.sections.fallback'])
      @endwhile
    @endif
  @endwhile
@endsection
