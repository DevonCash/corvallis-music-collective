<div
    id="room-availability-calendar-{{ $this->getId() }}"
    wire:id="{{ $this->getId() }}"
>
    <!-- Main Calendar Container -->
    <div>
        <!-- Header with title and booking button -->
        <header class="flex justify-between items-center gap-4 mb-4">
            <div>
                <h2 class="text-xl font-bold">{{ __('practice-space::room_availability_calendar.calendar_title', ['room' => $this->selectedRoom?->name]) }}</h2>
                        <!-- Room Policy Summary -->
                @if($this->selectedRoom && !empty($policyInfo))
                <div class="room-policy-summary text-sm">
                    <p class="text-gray-700">{{ $policyInfo }}</p>
                </div>
                @endif
                    
            </div>
            <div class="nowrap">
                <x-filament::button class='calendar-button' wire:click="mountAction('createBooking', {'room_id': {{ $this->selectedRoom?->id ?? 'null' }}})">
                    {{ __('practice-space::room_availability_calendar.create_booking') }}
                </x-filament::button>
            </div>
        </header>
        
        
        
        <!-- Calendar Container -->
        <div class="calendar-container">
            <!-- Room Selection and Date Navigation -->
            <div class="calendar-header">
                <div class="flex flex-1 md:flex-col justify-between gap-2 items-center md:items-start w-full">
                    <div class='calendar-header-title'>
                        <div>{{ $this->startDate->format('M j') }} - {{ $this->endDate->format('M j, Y') }}</div>
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
                            {{ __('practice-space::room_availability_calendar.today') }}
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
                    @if($this->cellData() && count($this->cellData()) > 0 && isset($this->cellData()[0]))
                    <div class="calendar-grid" 
                        style="
                            grid-template-columns: auto repeat({{ count($this->cellData()) }}, minmax(var(--width), 1fr));
                            grid-template-rows: auto repeat({{ count($this->cellData()[0] ?? []) }}, var(--height));
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
                                    'room_id': ev.target.dataset.roomId,
                                    'booking_date': ev.target.dataset.date, 
                                    'booking_time': ev.target.dataset.time,
                                });
                            }
                        }"
                        @mousemove="handleMouseMove"
                        @mouseleave="handleMouseLeave"
                        @click="openBookingForm"
                    >
                        <!-- Corner Cell (Time) -->
                        <div class="time-column-header" 
                            style="grid-column: 1; grid-row: 1; "
                            data-header>
                            {{ __('practice-space::room_availability_calendar.time') }}
                        </div>
                        
                        <!-- Date Headers -->
                        @foreach($this->cellData() as $dayIndex => $dayData)
                            @php
                                $date = $this->startDate->copy()->addDays($dayIndex);
                            @endphp
                            <div class="date-header" 
                                style="grid-column: {{ $dayIndex + 2 }}; grid-row: 1;"
                                data-header>
                                {{ $date->format('D, M j') }}
                            </div>
                        @endforeach

                        <!-- Time Labels Column -->
                        @foreach($this->cellData()[0] as $timeSlotIndex => $timeSlot)
                            @if($timeSlotIndex % 2 === 1)
                                @continue
                            @endif
                            @php 
                                $timeString = $timeSlot['time'];
                                $time = Carbon\Carbon::createFromFormat('H:i', $timeString);
                            @endphp
                            <div class="time-label" 
                                style="grid-column: 1; grid-row: {{ ($timeSlotIndex) + 2 }} / span 2; height: calc(var(--height) * 2);"
                                data-time-label>
                                {{ $time->format(__('practice-space::room_availability_calendar.time_format')) }}
                            </div>
                        @endforeach

                        <!-- Time Cells -->
                        @foreach($this->cellData() as $dayIndex => $dayData)
                            @foreach($dayData as $timeSlotIndex => $cell)
                                @php
                                    // Determine cell style based on invalid reason
                                    $cellStyle = '';
                                    $reasonClass = '';
                                    $tooltip = '';
                                    
                                    if ($cell['invalid_duration']) {
                                        switch ($cell['invalid_reason']) {
                                            case 'past':
                                                $reasonClass = 'time-cell-past';
                                                $tooltip = __('practice-space::room_availability_calendar.invalid_reason_past');
                                                break;
                                            case 'advance_notice':
                                                $reasonClass = 'time-cell-advance-notice';
                                                $tooltip = __('practice-space::room_availability_calendar.invalid_reason_advance_notice');
                                                break;
                                            case 'closing_time':
                                                $reasonClass = 'time-cell-closing-time';
                                                $tooltip = __('practice-space::room_availability_calendar.invalid_reason_closing_time');
                                                break;
                                            case 'adjacent_booking':
                                                $reasonClass = 'time-cell-adjacent-booking';
                                                $tooltip = __('practice-space::room_availability_calendar.invalid_reason_adjacent_booking');
                                                break;
                                            default:
                                                $reasonClass = 'time-cell-striped';
                                                $tooltip = __('practice-space::room_availability_calendar.unavailable_time_slot');
                                        }
                                    }
                                    
                                    // Set user booking style
                                    if ($cell['booking_id'] && $cell['is_current_user_booking']) {
                                        $cellStyle = 'background-color: rgba(229, 119, 30, 0.1);';
                                    }
                                @endphp
                                <div 
                                    class="time-cell {{ $cell['booking_id'] ? ($cell['is_current_user_booking'] ? 'time-cell-booked-by-user' : '') : '' }} {{ $reasonClass }}"
                                    style="grid-column: {{ $dayIndex + 2 }}; grid-row: {{ $timeSlotIndex + 2 }}; {{ $cellStyle }}"
                                    data-time-cell
                                    data-date="{{ $cell['date'] }}"
                                    data-time="{{ $cell['time'] }}"
                                    data-date-index="{{ $dayIndex }}"
                                    data-slot-index="{{ $timeSlotIndex }}"
                                    data-room-id="{{ $cell['room_id'] }}"
                                    data-booked="{{ $cell['booking_id'] ? 'true' : 'false' }}"
                                    data-invalid-duration="{{ $cell['invalid_duration'] ? 'true' : 'false' }}"
                                    data-invalid-reason="{{ $cell['invalid_reason'] ?? '' }}"
                                    title="{{ $cell['invalid_duration'] ? $tooltip : __('practice-space::room_availability_calendar.available_time_slot') }}"
                                    @if($cell['booking_id'])
                                        data-booking-id="{{ $cell['booking_id'] }}"
                                        data-is-current-user="{{ $cell['is_current_user_booking'] ? 'true' : 'false' }}"
                                    @endif
                                ></div>
                            @endforeach
                        @endforeach
                    
                        <!-- Bookings -->
                        @foreach(array_filter($this->bookings(), fn($booking) => $booking['is_current_user'] || Auth::user()->can('manage', \CorvMC\PracticeSpace\Models\Booking::class)) as $booking)
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

                        <!-- Cursor Element -->
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
                    @else
                    <div class="calendar-empty-state">
                        <p>{{ __('practice-space::room_availability_calendar.no_room_selected') }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        <x-filament-actions::modals /> 
    </div>
</div>