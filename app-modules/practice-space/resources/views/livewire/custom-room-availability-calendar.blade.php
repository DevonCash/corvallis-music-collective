<div class="overflow-hidden">
        <!-- Room Selection -->
        <div class="p-2 bg-base-200 border-b flex md:flex-row flex-col w-full gap-4">
            <div class="flex flex-1 md:flex-col justify-between gap-2  items-center md:items-start">
                <div class='text-lg font-semibold'>
                    {{ $startDate->format('M j') }} - {{ $endDate->format('M j, Y') }}
                </div>
                <div class="flex space-x-2">
                    <button 
                        wire:click="previousPeriod" 
                        class="px-3 py-1 bg-base-100 border border-neutral-400 rounded-md text-base-content hover:bg-base-200 disabled:opacity-50 disabled:cursor-not-allowed"
                        @if(!$this->canNavigateToPreviousPeriod()) disabled @endif
                    >
                        <x-heroicon-s-chevron-left class="w-5 h-5" />
                    </button>
                    <button 
                        wire:click="nextPeriod" 
                        class="px-3 py-1 bg-base-100 border border-neutral-400 rounded-md text-base-content hover:bg-base-200 disabled:opacity-50 disabled:cursor-not-allowed"
                        @if(!$this->canNavigateToNextPeriod()) disabled @endif
                    >
                        <x-heroicon-s-chevron-right class="w-5 h-5" />
                    </button>
                    <button 
                        wire:click="today" 
                        class="px-3 py-1 bg-base-100 border border-neutral-400 rounded-md text-base-content hover:bg-base-200"
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
                            
                            const cellData = ev.target.dataset;

                            if (cellData && cellData.booked !== 'true' && cellData.invalidDuration !== 'true') {
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
                        @php 
                            $date = $this->startDate->copy()->addDays($dateIndex);
                            $isToday = $date->isToday();
                            $isPast = $date->lt(now()->startOfDay());
                        @endphp
                        <div class="sticky top-0 border-b p-2 text-center font-medium {{ $isToday ? 'bg-info bg-opacity-10' : ($isPast ? 'bg-base-200' : 'bg-base-200') }}" 
                             style="grid-column: {{ $dateIndex + 2 }}; grid-row: 1;"
                             data-header>
                            {{ $date->format('D n/j') }}
                            @if($isToday)
                                <div class="text-xs text-info">Today</div>
                            @endif
                        </div>
                    @endforeach

                    <!-- Time Cells -->
                    @foreach($cellData as $dateIndex => $date)
                        @foreach($date as $slotIndex => $cell)
                            <div 
                                class="border-b border-r relative {{ $cell['booking_id'] ? ($cell['is_current_user_booking'] ? 'bg-primary bg-opacity-10' : '') : '' }}"
                                style="grid-column: {{ $dateIndex + 2 }}; grid-row: {{ $slotIndex + 2 }}; {{ ($cell['invalid_duration'] || $cell['booking_id']) ? 'background-image: linear-gradient(45deg, rgba(209, 213, 219, 0.3) 25%, transparent 25%, transparent 50%, rgba(209, 213, 219, 0.3) 50%, rgba(209, 213, 219, 0.3) 75%, transparent 75%, transparent); background-size: 16px 16px;' : '' }} {{ $cell['booking_id'] && $cell['is_current_user_booking'] ? 'background-color: rgba(229, 119, 30, 0.1);' : '' }}"
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
                    
                    <!-- Single Cursor Element -->
                    <div 
                        x-show="showCursor"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        class="pointer-events-none flex items-center justify-center bg-primary w-full h-full opacity-75"
                        :style="`
                            grid-column: ${cursorColumn}; 
                            grid-row: ${cursorRow};
                        `"
                    >
                        <div class="w-8 h-8 text-primary-content flex items-center justify-center">
                            <x-filament::icon icon='heroicon-m-plus' class='size-5'/>
                        </div>
                    </div>
                    
                    <!-- Bookings -->
                    @foreach(array_filter($bookings, fn($booking) => $booking['is_current_user'] || Auth::user()->can('manage', \CorvMC\PracticeSpace\Models\Booking::class)) as $booking)
                        <div 
                            class="{{ $booking['is_current_user'] ? 'bg-primary border-primary text-primary-content' : 'bg-base-100 border-neutral-400' }} border rounded-md p-2 text-xs m-1 shadow-sm"
                            style="
                                grid-column: {{ $booking['date_index'] + 2 }};
                                grid-row: {{ $booking['time_index'] + 2 }} / span {{ $booking['slots'] }};
                                position: relative;
                                pointer-events: auto;
                            "
                        >
                            <div class="font-medium">{{ $booking['title'] }}</div>
                            <div class="">{{ $booking['time_range'] }}</div>
                        </div>
                    @endforeach
                    @else
                    <div class="p-8 text-center text-base-content-secondary">
                        <p>Please select a room to view the calendar.</p>
                    </div>
                    @endif


                    <div class="sticky top-0 left-0 border-b border-r p-2 font-medium flex justify-center text-base-content-secondary bg-base-200" 
                    style="grid-column: 1; grid-row: 1;"
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
                                <div class="border-b border-r p-2 text-sm text-base-content-secondary bg-base-100 flex items-center justify-center" 
                                    style="grid-column: 1; grid-row: {{ ($timeSlotIndex) + 1 }} / span 2; height: calc(var(--height) * 2);"
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
</div> 