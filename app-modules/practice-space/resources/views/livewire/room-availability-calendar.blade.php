<div>
    <header class="flex justify-between items-center gap-4">
        <div>
    <h2 class="text-xl font-bold">Room Availability Calendar</h2>
    <p class="text-sm text-gray-500 mb-4">This calendar shows when rooms are booked. Your bookings are highlighted in blue and show your name, while other bookings are marked as "Booked".</p>
        </div>
    <div class="nowrap">
        <x-filament::button class='calendar-button' wire:click="mountAction('createBooking', {room_id: '@js($this->selectedRoom->id)'})">
            Book a Room
        </x-filament::button>
    </div>
    </header>
    <div class="calendar-container">
        <!-- Room Selection -->
        <div class="calendar-header">
            <div class="flex flex-1 md:flex-col justify-between gap-2 items-center md:items-start">
                <div class='calendar-header-title'>
                    {{ $startDate->format('M j') }} - {{ $endDate->format('M j, Y') }}
                </div>
                <div class="flex gap-1">
                    <button 
                        wire:click="previousPeriod" 
                        class="calendar-nav-button"
                        @if(!$this->canNavigateToPreviousPeriod()) disabled @endif
                    >
                        <x-heroicon-s-chevron-left class="w-5 h-5" />
                    </button>
                    <button 
                        wire:click="nextPeriod" 
                        class="calendar-nav-button"
                        @if(!$this->canNavigateToNextPeriod()) disabled @endif
                    >
                        <x-heroicon-s-chevron-right class="w-5 h-5" />
                    </button>
                    <button 
                        wire:click="today" 
                        class="calendar-nav-button"
                    >
                        Today
                    </button>
                </div>
            </div>
            @if(\CorvMC\PracticeSpace\Models\Room::count() > 1)
                <div class='flex-1'>
                    {{ $this->form }}
                </div>
            @endif
            
        </div>
        
        <!-- Scroll Container -->
        <div class="overflow-x-auto relative" style="--width: 10rem; --height: 2rem; overscroll-behavior-x: none;" x-data="{ hasScroll: false, scrollLeft: 0, scrollRight: false }" x-init="">
            
            <!-- Calendar Container -->
            <div class="w-fit">
                <!-- Calendar Grid -->
                <div class="calendar-grid" 
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
                                || cell.dataset.invalidDuration === 'true'
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
                            if(ev.target.dataset.invalidDuration === 'true') return;
                            
                            console.log(ev.target.dataset);

                            $wire.mountAction('createBooking', { 
                                room_id: {{ $this->selectedRoom->id }},
                                booking_date: ev.target.dataset.date, 
                                booking_time: ev.target.dataset.time,
                            });
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
                        @php 
                            $date = $this->startDate->copy()->addDays($dateIndex);
                            $today = \Carbon\Carbon::now($this->selectedRoom->timezone)->startOfDay();
                            $isToday = $date->isSameDay($today);
                            $isPast = $date->lt($today);
                        @endphp
                        <div class="date-header {{ $isToday ? 'date-header-today' : '' }}" 
                             style="grid-column: {{ $dateIndex + 2 }}; grid-row: 1;"
                             data-header>
                            {{ $date->format('D n/j') }}
                            @if($isToday)
                                <div class="date-header-today-label">Today</div>
                            @endif
                        </div>
                    @endforeach

                    <!-- Time Cells -->
                    @foreach($cellData as $dateIndex => $date)
                        @foreach($date as $slotIndex => $cell)
                            <div 
                                class="time-cell {{ $cell['invalid_duration'] || $cell['booking_id'] ? 'time-cell-striped' : '' }} {{ $cell['booking_id'] ? ($cell['is_current_user_booking'] ? 'time-cell-booked-by-user' : '') : '' }}"
                                style="grid-column: {{ $dateIndex + 2 }}; grid-row: {{ $slotIndex + 2 }};
                                {{ $cell['booking_id'] && $cell['is_current_user_booking'] ? 'background-color: rgba(229, 119, 30, 0.1);' : '' }}"
                                data-time-cell
                                data-date="{{ $cell['date'] }}"
                                data-time="{{ $cell['time'] }}"
                                data-date-index="{{ $dateIndex }}"
                                data-slot-index="{{ $cell['slot_index'] }}"
                                data-booked="{{ $cell['booking_id'] ? 'true' : 'false' }}"
                                data-invalid-duration="{{ $cell['invalid_duration'] ? 'true' : 'false' }}"
                                @if($cell['booking_id'])
                                    data-booking-id="{{ $cell['booking_id'] }}"
                                    data-is-current-user="{{ $cell['is_current_user_booking'] ? 'true' : 'false' }}"
                                @endif
                            ></div>
                        @endforeach
                    @endforeach
                
                    <!-- Bookings -->
                    @foreach(array_filter($bookings, fn($booking) => $booking['is_current_user'] || Auth::user()->can('manage', \CorvMC\PracticeSpace\Models\Booking::class)) as $booking)
                        <div 
                            class="booking {{ $booking['is_current_user'] ? 'booking-by-user' : 'booking-by-other' }}"
                            style="
                                grid-column: {{ $booking['date_index'] + 2 }};
                                grid-row: {{ $booking['time_index'] + 2 }} / span {{ $booking['slots'] }};
                                position: relative;
                                pointer-events: auto;
                            "
                        >
                            <div class="booking-title">{{ $booking['title'] }}</div>
                            <div>{{ $booking['time_range'] }}</div>
                        </div>
                    @endforeach
                    @else
                    <div class="calendar-empty-state">
                        <p>Please select a room to view the calendar.</p>
                    </div>
                    @endif


                    <div class="time-column-header" 
                    style="grid-column: 1; grid-row: 1; "
                    data-header>
                         Time
                    </div>
                    
                    <!-- Time Labels Column -->
                    <div class="sticky left-0 z-10" style="grid-column: 1; grid-row: 2 / span {{ count($cellData[0] ?? []) }};">
                        <!-- Time Labels -->
                        @if(isset($cellData[0]))
                            @foreach($cellData[0] as $timeSlotIndex => $timeSlot)
                                @if($timeSlotIndex % 2 === 1)
                                    @continue
                                @endif
                                @php 
                                    $timeString = $timeSlot['time'];
                                    $time = Carbon\Carbon::createFromFormat('H:i', $timeString);
                                @endphp
                                <div class="time-label" 
                                    style="grid-column: 1; grid-row: {{ ($timeSlotIndex) + 1 }} / span 2; height: calc(var(--height) * 2);"
                                    data-time-label>
                                    {{ $time->format('g:ia') }}
                                </div>
                            @endforeach
                        @endif
                    </div>
                    

                        
                    <!-- Single Cursor Element -->
                    <div 
                        x-show="showCursor"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        class="calendar-cursor"
                        :style="`
                            grid-column: ${cursorColumn}; 
                            grid-row: ${cursorRow};
                            top: -1px; 
                            left: -1px;
                            width: calc(100% + 1px);
                            height: calc(100% + 1px);
                        `"
                    >
                        <div class="calendar-cursor-icon">
                            <x-filament::icon icon='heroicon-m-plus' class='size-5'/>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
    <x-filament-actions::modals /> 
</div>