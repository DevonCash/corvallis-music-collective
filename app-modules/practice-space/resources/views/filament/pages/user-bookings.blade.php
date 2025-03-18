<x-filament-panels::page>
    <div class="space-y-6">
        <div>
            <h2 class="text-xl font-bold">My Upcoming Bookings</h2>
            <p class="text-sm text-gray-500 mb-4">This table shows your upcoming bookings. You can cancel a booking if you need to.</p>
            {{ $this->table }}
        </div>
        
        <div>
            
            @livewire('room-availability-calendar')
        </div>
    </div>
</x-filament-panels::page> 