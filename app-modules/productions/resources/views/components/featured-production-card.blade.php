@props(['production', 'showTickets' => true])

<div class="mx-auto card bg-base-100 shadow-xl hover:shadow-2xl transition-shadow duration-300 w-96 max-w-lg flex flex-col {{ $production->end_date < now() ? 'opacity-80' : '' }}">
    <a href="{{ route('productions.show', $production) }}" class="flex flex-col flex-1">
        <figure class="relative w-full aspect-[8.5/11] flex items-center justify-center overflow-hidden rounded-t-lg bg-gray-100">
            @if($production->poster)
                <img src="{{ Storage::url($production->poster) }}" alt="{{ $production->title }}" class="object-cover object-center w-full h-full" />
            @else
                <div class="flex flex-col items-center justify-center w-full h-full text-gray-400">
                    <svg class="w-10 h-10 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span class="text-xs">No Poster</span>
                </div>
            @endif
            @if($production->tags->isNotEmpty())
                <div class="absolute left-0 bottom-0 w-full flex gap-1 px-4 pb-2">
                    @foreach($production->tags as $tag)
                        <x-productions::production-tag :tag="$tag" />
                    @endforeach
                </div>
            @endif
        </figure>
        <div class="card-body flex flex-col flex-1 pt-2 pr-4 pb-0 pl-4 gap-2 bg-secondary min-h-0">
            <h2 class="card-title text-white text-base leading-tight mb-1 line-clamp-1">{{ $production->title }}</h2>
            <div class="text-xs text-white/90 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>{{ $production->start_date->format('M j, Y g:i A') }}</span>
            </div>
            @if($production->venue)
                <div class="text-xs text-white/90 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span class="line-clamp-1">{{ $production->venue->name }}</span>
                </div>
            @endif
            <div class="flex-1"></div>
        </div>
    </a>
    <div class="flex w-full mt-0">
        <a href="{{ $showTickets ? $production->ticket_url : route('productions.show', $production) }}"
           class="w-full rounded-none rounded-b-lg flex items-center justify-center text-base py-3 font-bold transition-colors duration-200 bg-primary text-white hover:bg-primary/80"
           @if($showTickets) target="_blank" rel="noopener noreferrer" @endif
        >{{ $showTickets ? 'Get Tickets' : 'View Details' }}</a>
    </div>
</div> 