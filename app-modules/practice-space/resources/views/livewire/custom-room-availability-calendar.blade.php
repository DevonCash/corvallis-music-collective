<div>
    <div class="mb-4">
        {{ $this->form }}
    </div>
    
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <!-- Calendar Header -->
        <div class="flex justify-between items-center p-4 bg-gray-50 border-b">
            <div class="flex space-x-2">
                <button 
                    wire:click="previousPeriod" 
                    class="px-3 py-1 bg-white border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                >
                    <x-heroicon-s-chevron-left class="w-5 h-5" />
                </button>
                <button 
                    wire:click="nextPeriod" 
                    class="px-3 py-1 bg-white border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                >
                    <x-heroicon-s-chevron-right class="w-5 h-5" />
                </button>
                <button 
                    wire:click="today" 
                    class="px-3 py-1 bg-white border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                >
                    Today
                </button>
            </div>
            <div class='flex items-center gap-4'>
            
            <div>
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model="view">
                        <option value="week">Week</option>
                        <option value="day">Day</option>
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>
            <span class="text-lg font-semibold">
                @if($view === 'day')
                    {{ $startDate->format('F j, Y') }}
                @else
                    {{ $startDate->format('M j') }} - {{ $endDate->format('M j, Y') }}
                @endif
            </span>
        </div>
        </div>
        
        <!-- Room List -->
        <div class="p-4 bg-gray-50 border-b">
            <h3 class="font-medium text-gray-700 mb-2">Rooms:</h3>
            <div class="flex flex-wrap gap-2">
                @foreach($rooms as $room)
                    <div class="px-3 py-1 bg-white border border-gray-300 rounded-md text-sm">
                        {{ $room['name'] }}
                    </div>
                @endforeach
            </div>
        </div>
        
        <!-- Calendar Grid -->
        <div class="overflow-x-auto">
            @php $width = '9rem' @endphp
            <div class="min-w-full">
                <!-- Calendar Header -->
                <div class="grid " style="grid-template-columns: 100px repeat({{ count($dates) }}, minmax({{ $width }}, 1fr))">
                    <!-- Empty corner cell -->
                    <div class="border-b border-r p-2 font-medium text-gray-500 bg-gray-50 z-10 sticky left-0 ">
                        Time
                    </div>
                    
                    <!-- Date Headers -->
                    @foreach($dates as $date)
                        <div class="border-b p-2 text-center font-medium {{ $date['is_today'] ? 'bg-blue-50' : 'bg-gray-50' }}" style='min-width: {{ $width }}'>
                            {{ $date['display_date'] }}
                        </div>
                    @endforeach
                </div>
                
                <!-- Calendar Body -->
                <div class="relative">
                    <!-- Time Grid -->
                    <div class="grid " style="grid-template-columns: 100px repeat({{ count($dates) }}, minmax({{ $width }}, 1fr))">
                        @foreach($timeSlots as $timeSlot)
                            <!-- Time Label -->
                            <div class="border-b border-r p-2 h-16 text-sm text-gray-500 bg-white sticky left-0 z-10">
                                {{ $timeSlot['display_time'] }}
                            </div>
                            
                            <!-- Time Cells -->
                            @foreach($dates as $date)
                                <div class="border-b border-r p-2 h-16 relative" style='min-width: {{ $width }}'>
                                    <!-- Empty cell for grid structure -->
                                </div>
                            @endforeach
                        @endforeach
                    </div>
                    
                    <!-- Bookings Overlay -->
                    <div class="absolute top-0 left-0 w-full h-full pointer-events-none">
                        <div class="grid" style="grid-template-columns: 100px repeat({{ count($dates) }}, minmax({{ $width }}, 1fr))">
                            <!-- Skip time column -->
                            <div class="col-span-1"></div>
                            
                            <!-- Booking columns -->
                            @foreach($dates as $dateIndex => $date)
                                <div class="relative">
                                    @foreach($rooms as $room)
                                        @foreach($timeSlots as $timeSlotIndex => $timeSlot)
                                            @php
                                                $slotData = $this->getBookingForSlot(
                                                    $date['date'], 
                                                    $room['id'], 
                                                    $timeSlot['slot_index']
                                                );
                                            @endphp
                                            
                                            @if($slotData && $slotData['is_start'])
                                                @php
                                                    $booking = $slotData['booking'];
                                                    $height = $slotData['span'] * 4; // 4rem per hour
                                                    $topPosition = $timeSlotIndex * 4; // 4rem per hour
                                                    
                                                    // Add margin if starts on half hour
                                                    $marginTop = $booking['starts_on_half_hour'] ? '2rem' : '0';
                                                @endphp
                                                
                                                <div 
                                                    class="{{ $booking['is_current_user'] ? 'bg-blue-100 border-blue-300' : 'bg-gray-100 border-gray-300' }} border rounded-md p-2 text-xs absolute pointer-events-auto z-10"
                                                    style="top: {{ $topPosition }}rem; margin-top: {{ $marginTop }}; height: {{ $height }}rem; left: 0.5rem; right: 0.5rem;"
                                                >
                                                    <div class="font-medium">{{ $booking['title'] }}</div>
                                                    <div class="text-gray-500">{{ $booking['time_range'] }}</div>
                                                    <div class="text-gray-500">{{ $booking['room_name'] }}</div>
                                                </div>
                                            @endif
                                        @endforeach
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
        </div>
    </div>
</div> 