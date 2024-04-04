@extends('layouts.index', ['title' => 'Events', 'pagination' => $events->links() ])

@section("content")

<ul>
    @foreach( $events as $event)
    <li class='mb-6'>
        <x-event-card :event="$event" />
    </li>
    @endforeach
</ul>
@endsection
