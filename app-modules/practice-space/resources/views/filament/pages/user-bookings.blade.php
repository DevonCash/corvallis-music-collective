<x-filament-panels::page>
    <div class="space-y-6">
        <div>
            <h2 class="text-xl font-bold">My Upcoming Bookings</h2>
            <p class="text-sm text-gray-500 mb-4">This table shows your upcoming bookings. You can cancel a booking if you need to.</p>
            {{ $this->table }}
        </div>
        
        <div>
            <h2 class="text-xl font-bold">Room Availability Calendar</h2>
            <p class="text-sm text-gray-500 mb-4">This calendar shows when rooms are booked. Your bookings are highlighted in blue and show your name, while other bookings are marked as "Booked".</p>
            @livewire('custom-room-availability-calendar')
        </div>
    </div>
</x-filament-panels::page> 