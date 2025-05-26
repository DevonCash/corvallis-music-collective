@props(['tag'])

@php
    $colorClass = match($tag->type) {
        'genre' => 'bg-base-200 text-base-content',
        'event' => 'bg-primary text-primary-content',
        'age' => 'bg-secondary text-secondary-content',
        'price' => 'bg-accent text-accent-content',
        default => 'bg-base-200 text-base-content'
    };
@endphp

<span class="text-xs px-2 py-0.5 {{ $colorClass }} rounded-full shadow-sm whitespace-nowrap">{{ $tag->name }}</span> 