@extends('layouts.bare', ['title' => $title])

@section('body')
    <x-page-header />

    @yield('content')
@endsection
