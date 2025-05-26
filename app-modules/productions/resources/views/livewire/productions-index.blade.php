<div class="max-w-7xl mx-auto px-4 py-8">
    <!-- Upcoming Shows -->
    <div class="mb-10">
        <h2 class="text-2xl font-bold mb-4 text-primary">Upcoming Events</h2>
        <div class="flex gap-6 overflow-x-auto overflow-y-visible py-6 pb-10 -mb-10 snap-x snap-mandatory" id="upcoming-shows-scroll">
            @forelse($upcomingShows as $i => $production)
                <div class="snap-start flex-shrink-0" id="upcoming-card-{{ $i }}">
                    <x-productions::featured-production-card :production="$production" />
                </div>
            @empty
                <div class="text-gray-400">No upcoming events found.</div>
            @endforelse
        </div>
        <div id="upcoming-scroll-indicator" class="flex justify-center gap-2 mt-4">
            @foreach($upcomingShows as $i => $production)
                <span class="dot w-3 h-3 rounded-full bg-gray-300 transition-colors duration-200" data-index="{{ $i }}"></span>
            @endforeach
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const cards = Array.from(document.querySelectorAll('[id^="upcoming-card-"]'));
            const dots = Array.from(document.querySelectorAll('#upcoming-scroll-indicator .dot'));
            if (!cards.length || !dots.length) return;
            const scrollContainer = document.getElementById('upcoming-shows-scroll');

            // Track intersection state for all cards
            const visibilityMap = new Map();

            const observer = new IntersectionObserver((entries) => {
                // Update visibility map for changed entries
                entries.forEach(entry => {
                    visibilityMap.set(entry.target, entry.intersectionRatio > 0);
                });
                // Remove highlight from all dots
                dots.forEach(dot => dot.classList.remove('bg-primary'));
                // Highlight dots for all cards currently visible
                cards.forEach((card, idx) => {
                    if (visibilityMap.get(card)) {
                        dots[idx]?.classList.add('bg-primary');
                    }
                });
            }, {
                root: scrollContainer,
                threshold: 0
            });

            cards.forEach(card => observer.observe(card));
        });
        </script>
    </div>

    <!-- All Events (Search Results) -->
    <div>
        <div class="flex justify-between items-center mb-4">
            <h2 id="results" class="text-xl font-semibold text-secondary">All Events</h2>
            <div class="flex gap-2">
                <button wire:click="sortBy('start_date')" class="btn btn-ghost btn-sm">
                    Date
                    @if($sortBy === 'start_date')
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            @if($sortDirection === 'asc')
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                            @else
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            @endif
                        </svg>
                    @endif
                </button>
                <button wire:click="sortBy('title')" class="btn btn-ghost btn-sm">
                    Title
                    @if($sortBy === 'title')
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            @if($sortDirection === 'asc')
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                            @else
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            @endif
                        </svg>
                    @endif
                </button>
            </div>
        </div>

        <!-- Search & Filter Bar -->
        <div class="mb-8">
            {{ $this->form }}
        </div>

        <!-- Top Pagination -->
        <div class="mb-6">
            {{ $productions->links() }}
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($productions as $production)
                <x-productions::production-card :production="$production" />
            @empty
                <div class="col-span-full text-gray-400">No productions found.</div>
            @endforelse
        </div>

        <!-- Bottom Pagination -->
        <div class="mt-8">
            {{ $productions->links() }}
        </div>
    </div>
</div>
