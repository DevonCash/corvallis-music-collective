@extends('layouts.show', ['title' => $post->title])

@section('content')
        <div class='w-min mx-2 sm:mx-auto col-start-2 flex flex-col'>
            <header class='my-8'>
                <nav class='breadcrumbs display lowercase text-sm'>
                    <ul >
                        <li>
                            <a href='/news' class='link'>News</a>
                        </li>
                        <li>
                            {{$post->title}}
                        </li>
                    </ul>
                </nav>
                <hgroup>
                    <h2 class='text-xl sm:text-3xl display lowercase'>{{ $post->title }}</h2>
                </hgroup>
            </header>

            <div class='flex'>
                <time class='bg-base-100 w-full display lowercase'>
                    <span class='inline-block p-3 mr-3 bg-primary text-primary-content'>{{$post->published_at->format('F j')}}</span>
                    <span>{{$post->published_at->format('g:i A')}}
                </time>
            </div>
            <article class='bg-white shadow p-6 flex flex-col '>
                <x-markdown class='prose max-w-prose w-screen'>{{$post->content}}</x-markdown>
            </article>
        </div>
@endsection
