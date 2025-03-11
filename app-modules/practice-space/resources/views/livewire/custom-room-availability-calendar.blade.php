<div>
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
            <div>
                <span class="text-lg font-semibold">
                    {{ $startDate->format('M j') }} - {{ $endDate->format('M j, Y') }}
                </span>
            </div>
        </div>
        
        <!-- Room Selection -->
        <div class="p-4 bg-gray-50 border-b">
            <div class="max-w-md">
                {{ $this->form }}
            </div>
        </div>
        
        <!-- Scroll Container -->
        <div class="overflow-x-auto" style="--width: 10rem; --height: 2rem;">
            <!-- Calendar Container -->
            <div class="w-fit">
                <!-- Calendar Grid -->
                <div class="grid relative" 
                    style="
                        grid-template-columns: auto repeat({{ count($cellData) }}, minmax(var(--width), 1fr));
                        grid-template-rows: auto repeat({{ count($cellData[0] ?? []) }}, var(--height));
                        grid-auto-flow: dense;
                    "
                    x-data="{
                        showCursor: false,
                        cursorColumn: 0,
                        cursorRow: 0,
                                                
                        handleMouseMove(event) {
                            const cell = document.elementFromPoint(event.clientX, event.clientY);

                            if(
                                cell.dataset.timeCell === undefined
                                || cell.dataset.booked === 'true'
                            ) {
                                this.showCursor = false;
                                return;
                            }

                            this.showCursor = true;
                            this.cursorColumn = parseInt(cell.dataset.dateIndex) + 2;
                            this.cursorRow = parseInt(cell.dataset.slotIndex) + 2;
                        },
                        
                        handleMouseLeave() {
                            this.showCursor = false;
                        },
                        
                        openBookingForm(ev) {
                            if(ev.target.dataset.timeCell === undefined) return;
                            if(ev.target.dataset.booked === 'true') return;
                            
                            const cellData = ev.target.dataset;

                            if (cellData && cellData.booked !== 'true') {
                                $wire.dispatch('open-booking-form', { 
                                    date: cellData.date, 
                                    time: cellData.time,
                                    room_id: $wire.get('selectedRoom')
                                });
                            }
                        }
                    }"
                    @mousemove="handleMouseMove"
                    @mouseleave="handleMouseLeave"
                    @click="openBookingForm"
                >
                    @if(count($cellData) > 0 && isset($cellData[0]))
                    <!-- Header Row -->
                    <!-- Corner Cell (Time) -->
                   
                    
                    <!-- Date Headers -->
                    @foreach($cellData as $dateIndex => $_)
                        @php $date = $this->startDate->copy()->addDays($dateIndex); @endphp
                        <div class="sticky top-0 border-b p-2 text-center font-medium {{ $date->isToday() ? 'bg-blue-50' : 'bg-gray-50' }}" 
                             style="grid-column: {{ $dateIndex + 2 }}; grid-row: 1;"
                             data-header>
                            {{ $date->format('D n/j') }}
                        </div>
                    @endforeach

                    <!-- Time Cells -->
                    @foreach($cellData as $dateIndex => $date)
                        @foreach($date as $slotIndex => $cell)
                            <div 
                                class="border-b border-r relative {{ $cell['booking_id'] ? ($cell['is_current_user_booking'] ? 'bg-blue-50' : 'bg-gray-100') : '' }}"
                                style="grid-column: {{ $dateIndex + 2 }}; grid-row: {{ $slotIndex + 2 }};"
                                data-time-cell
                                data-date="{{ $cell['date'] }}"
                                data-time="{{ $cell['time'] }}"
                                data-date-index="{{ $dateIndex }}"
                                data-slot-index="{{ $cell['slot_index'] }}"
                                data-booked="{{ $cell['booking_id'] ? 'true' : 'false' }}"
                                @if($cell['booking_id'])
                                    data-booking-id="{{ $cell['booking_id'] }}"
                                    data-is-current-user="{{ $cell['is_current_user_booking'] ? 'true' : 'false' }}"
                                @endif
                            ></div>
                        @endforeach
                    @endforeach
                    
                    <!-- Single Cursor Element -->
                    <div 
                        x-show="showCursor"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        class=" pointer-events-none flex items-center justify-center bg-secondary w-full h-full opacity-75"
                        :style="`
                            grid-column: ${cursorColumn}; 
                            grid-row: ${cursorRow};
                        `"
                    >
                        <div class="w-8 h-8 bg-primary-600 text-white flex items-center justify-center">
                            <x-filament::icon icon='heroicon-m-plus' class='size-5'/>
                        </div>
                    </div>
                    
                    <!-- Bookings -->
                    @foreach(array_filter($bookings, fn($booking) => $booking['is_current_user'] || Auth::user()->can('manage', \CorvMC\PracticeSpace\Models\Booking::class)) as $booking)
                        <div 
                            class="{{ $booking['is_current_user'] ? 'bg-blue-100 border-blue-300' : 'bg-gray-100 border-gray-300' }} border rounded-md p-2 text-xs m-1"
                            style="
                                grid-column: {{ $booking['date_index'] + 2 }};
                                grid-row: {{ $booking['time_index'] + 2 }} / span {{ $booking['slots'] }};
                                position: relative;
                                pointer-events: auto;
                            "
                        >
                            <div class="font-medium">{{ $booking['title'] }}</div>
                            <div class="text-gray-500">{{ $booking['time_range'] }}</div>
                        </div>
                    @endforeach
                    @else
                    <div class="p-8 text-center text-gray-500">
                        <p>Please select a room to view the calendar.</p>
                    </div>
                    @endif


                    <div class="sticky top-0 left-0 border-b border-r p-2 font-medium text-gray-500 bg-gray-50" 
                    style="grid-column: 1; grid-row: 1;"
                    data-header>
                         Time
                    </div>
                    
                    <!-- Time Labels Column -->
                    @if(isset($cellData[0]))
                        @foreach($cellData[0] as $timeSlotIndex => $timeSlot)
                            {{-- Skip every other label to avoid crowding --}}
                            @if($timeSlotIndex % 2 === 1)
                                @continue
                            @endif
                            @php 
                                // Parse the time from the cell data
                                $timeString = $timeSlot['time'];
                                $time = Carbon\Carbon::createFromFormat('H:i', $timeString);
                            @endphp
                            <div class="sticky left-0 z-10 border-b border-r p-2 text-sm text-gray-500 bg-white flex items-center justify-center" 
                                style="grid-column: 1; grid-row: {{ ($timeSlotIndex) + 2 }} / span 2;"
                                data-time-label>
                                {{ $time->format('g:ia') }}
                            </div>
                        @endforeach
                    @endif
                    
                </div>
            </div>
        </div>
    </div>
</div> 