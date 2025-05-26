@extends('layouts.app')

@section('content')
<main class="grow">
    <section class="container mx-auto px-4 py-8 max-w-5xl">
        <a href="{{ route('productions.index') }}" class="btn btn-link mb-6 text-secondary">&larr; {{ __('productions::events.back_to_events') }}</a>

        <div class="bg-base-100 flex flex-col lg:flex-row">
            <!-- Poster -->
            <div class="lg:w-1/3 flex-shrink-0 flex items-start justify-center p-6 lg:p-8">
                @if($production->poster)
                    <figure class="aspect-[7/9] w-full max-w-xs overflow-hidden rounded-lg bg-base-200">
                        <img src="{{ Storage::url($production->poster) }}" alt="{{ $production->title }} poster" class="object-cover object-center w-full h-full">
                    </figure>
                @else
                    <figure class="aspect-[7/9] w-full max-w-xs overflow-hidden rounded-lg bg-base-200 flex items-center justify-center">
                        <span class="text-base-content/50 text-2xl">No Poster</span>
                    </figure>
                @endif
            </div>
            <!-- Info -->
            <div class="flex-1 flex flex-col gap-4 p-6 lg:p-8">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <h1 class="card-title text-3xl font-bold text-primary mb-0">{{ $production->title }}</h1>
                    @if($production->status->getName() === 'active')
                        <span class="badge badge-success badge-lg gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                            </svg>
                            Happening Now
                        </span>
                    @endif
                </div>
                <div class="flex flex-col gap-2 text-base-content/80">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span>{{ $production->start_date->format('F j, Y g:i A') }}</span>
                    </div>
                    @if($production->venue)
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span>{{ $production->venue->name }}</span>
                            @if(is_array($production->venue->address))
                                <span class="ml-2 text-base-content/60">
                                    {{ $production->venue->address['street'] ?? '' }}
                                    {{ $production->venue->address['city'] ?? '' }},
                                    {{ $production->venue->address['state'] ?? '' }}
                                    {{ $production->venue->address['postal_code'] ?? '' }}
                                </span>
                            @endif
                        </div>
                    @endif
                </div>
                @if($production->ticket_link)
                    <a href="{{ $production->ticket_link }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-lg w-full mt-2">Get Tickets</a>
                @endif
                @if($production->description)
                    <div class="prose max-w-none text-base-content mt-2">{!! nl2br(e($production->description)) !!}</div>
                @endif
                @if($production->tags && $production->tags->count())
                    <div class="mt-2">
                        <h2 class="font-semibold text-base-content mb-2">Tags</h2>
                        <div class="flex flex-wrap gap-2">
                            @foreach($production->tags as $tag)
                                <x-productions::production-tag :tag="$tag" />
                            @endforeach
                        </div>
                    </div>
                @endif
                @if($production->acts && $production->acts->count())
                    <div class="mt-4">
                        <h2 class="font-semibold text-base-content mb-2">Acts</h2>
                        <div class="flex flex-col gap-4">
                            @foreach($production->acts as $act)
                                <div class="card bg-base-200 shadow-sm border border-base-300">
                                    <div class="card-body p-4">
                                        <h3 class="text-xl font-bold text-primary mb-1">{{ $act->name }}</h3>
                                        @if($act->description)
                                            <p class="text-base-content/80 mb-2">{{ $act->description }}</p>
                                        @endif
                                        <div class="flex flex-wrap items-center gap-3 mb-2">
                                            @if($act->website)
                                                <a href="{{ $act->website }}" target="_blank" rel="noopener noreferrer" class="btn btn-xs btn-ghost px-2" title="{{ $act->website }}">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4a8 8 0 100 16 8 8 0 000-16zm0 0c3.314 0 6 3.134 6 7s-2.686 7-6 7-6-3.134-6-7 2.686-7 6-7zm0 0v14m7-7H5" />
                                                    </svg>
                                                </a>
                                            @endif
                                            @if(is_array($act->social_links))
                                                @foreach($act->social_links as $social)
                                                    <a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer" class="btn btn-xs btn-ghost px-2" title="{{ ucfirst($social['platform']) }}">
                                                        @if($social['platform'] === 'facebook')
                                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M22 12c0-5.522-4.477-10-10-10S2 6.478 2 12c0 5 3.657 9.127 8.438 9.877v-6.987h-2.54v-2.89h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.242 0-1.632.771-1.632 1.562v1.875h2.773l-.443 2.89h-2.33v6.987C18.343 21.127 22 17 22 12z"/></svg>
                                                        @elseif($social['platform'] === 'instagram')
                                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M7.75 2h8.5A5.75 5.75 0 0 1 22 7.75v8.5A5.75 5.75 0 0 1 16.25 22h-8.5A5.75 5.75 0 0 1 2 16.25v-8.5A5.75 5.75 0 0 1 7.75 2zm0 1.5A4.25 4.25 0 0 0 3.5 7.75v8.5A4.25 4.25 0 0 0 7.75 20.5h8.5A4.25 4.25 0 0 0 20.5 16.25v-8.5A4.25 4.25 0 0 0 16.25 3.5h-8.5zm4.25 3.25a5.25 5.25 0 1 1 0 10.5a5.25 5.25 0 0 1 0-10.5zm0 1.5a3.75 3.75 0 1 0 0 7.5a3.75 3.75 0 0 0 0-7.5zm5.25.75a1 1 0 1 1 0 2a1 1 0 0 1 0-2z"/></svg>
                                                        @elseif($social['platform'] === 'spotify')
                                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm4.29 14.42a.75.75 0 0 1-1.03.23c-2.82-1.73-6.37-2.12-10.59-1.16a.75.75 0 1 1-.32-1.47c4.57-1.01 8.5-.57 11.67 1.27a.75.75 0 0 1 .23 1.03zm1.44-2.62a.75.75 0 0 1-1.04.25c-3.23-2-8.18-2.59-11.18-1.42a.75.75 0 1 1-.5-1.42c3.36-1.18 8.7-.54 12.3 1.62a.75.75 0 0 1 .25 1.04zm.13-2.68c-3.7-2.19-9.86-2.39-13.36-1.31a.75.75 0 1 1-.44-1.44c3.85-1.18 10.5-.95 14.6 1.44a.75.75 0 1 1-.8 1.31z"/></svg>
                                                        @elseif($social['platform'] === 'youtube')
                                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M21.8 8.001s-.2-1.4-.8-2c-.7-.8-1.5-.8-1.9-.9C16.1 5 12 5 12 5h-.1s-4.1 0-7.1.1c-.4 0-1.2.1-1.9.9-.6.6-.8 2-.8 2S2 9.6 2 11.2v1.6c0 1.6.2 3.2.2 3.2s.2 1.4.8 2c.7.8 1.7.8 2.1.9 1.5.1 6.9.1 6.9.1s4.1 0 7.1-.1c.4 0 1.2-.1 1.9-.9.6-.6.8-2 .8-2s.2-1.6.2-3.2v-1.6c0-1.6-.2-3.2-.2-3.2zM9.8 15.3V8.7l6.4 3.3-6.4 3.3z"/></svg>
                                                        @endif
                                                    </a>
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>
</main>
@endsection
