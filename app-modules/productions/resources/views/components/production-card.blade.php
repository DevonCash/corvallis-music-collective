@props(['production'])

<a href="{{ route('productions.show', $production) }}" class="block">
    <div class="card mx-auto bg-base-100 border border-secondary/40 shadow-md hover:shadow-lg transition-shadow duration-300 flex flex-row opacity-90 h-40 rounded-xl overflow-hidden w-full max-w-md group">
        <figure class="flex-shrink-0 aspect-[8.5/11] bg-gray-100 flex items-center justify-center relative rounded-l-xl rounded-r-none overflow-hidden -mr-[8px]">
            @if($production->poster)
                <img src="{{ Storage::url($production->poster) }}" alt="{{ $production->title }}" class="object-cover object-center w-full h-full aspect-[8.5/11]" />
            @else
                <span class="text-gray-400 text-sm font-medium text-center w-full">No Poster</span>
            @endif
        </figure>
        <div class="flex flex-col flex-1 z-10">
            <div class="flex-1 pt-3 pr-4 pl-4 gap-1 bg-base-100 text-secondary flex flex-col justify-between">
                <h2 class="card-title text-base font-bold leading-tight mb-1 line-clamp-1 text-primary group-hover:underline">{{ $production->title }}</h2>
                <div class="text-xs flex items-center gap-2 mb-0.5 text-gray-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>{{ $production->start_date->format('F j, Y') }}</span>
                </div>
                @if($production->venue)
                    <div class="text-xs flex items-center gap-2 mb-0.5 text-gray-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span class="line-clamp-1">{{ $production->venue->name }}</span>
                    </div>
                @endif
                @if($production->tags->isNotEmpty())
                    <div class="flex gap-1 mt-1 mb-1 overflow-x-scroll max-w-full">
                        @foreach($production->tags as $tag)
                            <x-productions::production-tag :tag="$tag" />
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="btn btn-sm w-full btn-primary rounded-none rounded-b-xl font-semibold tracking-wide text-white border-0 transition-colors duration-200">
                View Details
            </div>
        </div>
    </div>
</a> 