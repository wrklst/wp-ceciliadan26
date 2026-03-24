@extends('layouts.app')

@section('content')
  <h1>{{ __('Page not found', 'sage') }}</h1>

  <p>{{ __('The page you are looking for does not exist.', 'sage') }}</p>

  <a href="{{ home_url('/') }}">{{ __('Return to the homepage', 'sage') }}</a>
@endsection
