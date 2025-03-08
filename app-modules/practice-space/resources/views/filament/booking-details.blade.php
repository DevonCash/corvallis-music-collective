<div class="space-y-4">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Room</h3>
            <p class="mt-1 text-sm">{{ $booking->room->name }}</p>
        </div>
        
        <div>
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</h3>
            <p class="mt-1 text-sm">
                @php
                    $color = match($booking->state) {
                        'reserved' => 'warning',
                        'confirmed' => 'success',
                        'checked_in' => 'success',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    };
                @endphp
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-{{ $color }}-100 text-{{ $color }}-800 dark:bg-{{ $color }}-500/10 dark:text-{{ $color }}-500">
                    {{ ucfirst($booking->state) }}
                </span>
            </p>
        </div>
        
        <div>
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Start Time</h3>
            <p class="mt-1 text-sm">{{ $booking->start_time->format('M d, Y g:i A') }}</p>
        </div>
        
        <div>
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">End Time</h3>
            <p class="mt-1 text-sm">{{ $booking->end_time->format('M d, Y g:i A') }}</p>
        </div>
        
        <div>
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Duration</h3>
            <p class="mt-1 text-sm">{{ $booking->start_time->diffInHours($booking->end_time) }} hours</p>
        </div>
        
        <div>
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Price</h3>
            <p class="mt-1 text-sm">${{ number_format($booking->total_price, 2) }}</p>
        </div>
    </div>
    
    @if($booking->notes)
    <div>
        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Notes</h3>
        <p class="mt-1 text-sm">{{ $booking->notes }}</p>
    </div>
    @endif
    
    <div>
        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Room Details</h3>
        <div class="mt-1 text-sm">
            <p><strong>Category:</strong> {{ $booking->room->category->name ?? 'N/A' }}</p>
            <p><strong>Capacity:</strong> {{ $booking->room->capacity ?? 'N/A' }} people</p>
            @if($booking->room->specifications)
                <p><strong>Specifications:</strong> 
                    @foreach($booking->room->specifications as $spec => $value)
                        {{ $spec }}: {{ $value }}{{ !$loop->last ? ', ' : '' }}
                    @endforeach
                </p>
            @endif
        </div>
    </div>
</div> 