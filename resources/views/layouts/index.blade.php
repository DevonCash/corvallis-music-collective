@extends('layouts.bare', ['title' => $title])

@section('body')
    <x-page-header />
    <main class='page bg-gray-100 h-full px-4'>
        <div class='w-full max-w-prose mx-auto col-start-2'>
            <header class='border-b-2 border-primary w-full flex justify-between my-10'>
            <hgroup>
            <h2 class='text-6xl display'>{{ $title }}</h2>
            </hgroup>
            @yield('header')
            </header>
            @yield('content')
            <footer class='mt-10 mb-6'>
                {!! $pagination !!}
            </footer>
        </div>
    </main>
@endsection
