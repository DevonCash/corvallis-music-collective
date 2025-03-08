@php
    $activities = $getRecord()->activities()->orderByDesc('created_at')->get();
@endphp

<ul class="timeline timeline-vertical">
    @foreach($activities->reverse() as $activity)
        @php
            $changes = $activity->changes();
            $oldState = $changes['old']['state'] ?? null;
            $newState = $changes['attributes']['state'] ?? null;
            if (!$newState) continue;
            
            // Get just the class name without namespace
            $newState = strtolower(class_basename($newState));
            
            // Define point classes based on state
            $pointClass = match($newState) {
                'scheduled' => 'text-blue-500',
                'confirmed' => 'text-primary',
                'checked_in' => 'text-success',
                'completed' => 'text-success',
                'cancelled' => 'text-error',
                'no_show' => 'text-warning',
                default => 'text-gray-500'
            };
        @endphp
        
        <li>
            @if(!$loop->first)<hr/>@endif
            <div class="timeline-start timeline-box hidden sm:block">
                <div class="font-bold uppercase text-sm {{ $pointClass }}">
                    {{ $newState }}
                </div>
            </div>
            <div class="timeline-middle mx-2">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 {{ $pointClass }}">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="timeline-end">
            <div class="text-sm ">
                    {{ $activity->causer?->name ?? 'System' }}
                </div>
                <div class="font-mono text-xs opacity-50 truncate">
                    <span x-data x-init="$el.textContent = new Date('{{ $activity->created_at->toISOString() }}').toLocaleString('en-US', { 
                        year: 'numeric',
                        month: 'numeric',
                        day: 'numeric',
                        hour: 'numeric',
                        minute: '2-digit',
                        hour12: true
                    })"></span>
                </div>
                
            </div>
            @if(!$loop->last)<hr/>@endif
        </li>
    @endforeach
</ul>

@if($activities->isEmpty())
    <div class="text-center text-gray-500 py-4">
        No status changes recorded
    </div>
@endif 