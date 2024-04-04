@extends('layouts.index', ['title' => 'News', 'pagination' => $posts->links() ])

@section("content")

    <ul>
    @foreach( $posts as $post)
    <li class='mb-6'>
        <x-post-card :post="$post" />
    </li>
    @endforeach
    </ul>

@endsection
