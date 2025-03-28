<div
    id="room-availability-calendar-{{ $this->getId() }}"
    wire:id="{{ $this->getId() }}"
>
    <!-- Main Calendar Container -->
    <div class="space-y-4">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-bold">{{ __('practice-space::room_availability_calendar.calendar_title', ['room' => $this->room->name]) }}</h2>
            
            @if($this->room && !empty($policyInfo))
                <div class="text-sm text-gray-600">
                    {{ $policyInfo }}
                </div>
            @endif
        </div>

        <div class="flex justify-between items-center">
            <div class="flex space-x-2">
                <x-filament::button class='calendar-button' wire:click="mountAction('createBooking', {'room_id': {{ $this->room->id ?? 'null' }}})">
                    {{ __('practice-space::room_availability_calendar.create_booking') }}
                </x-filament::button>
            </div>
            
            <div class="flex space-x-2">
                <x-filament::button class='calendar-button' wire:click="previousPeriod" :disabled="!$this->canNavigateToPreviousPeriod()">
                    {{ __('practice-space::room_availability_calendar.previous_week') }}
                </x-filament::button>
                
                <x-filament::button class='calendar-button' wire:click="today">
                    {{ __('practice-space::room_availability_calendar.today') }}
                </x-filament::button>
                
                <x-filament::button class='calendar-button' wire:click="nextPeriod" :disabled="!$this->canNavigateToNextPeriod()">
                    {{ __('practice-space::room_availability_calendar.next_week') }}
                </x-filament::button>
            </div>
        </div>

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
                    @php($dayCount = $this->endDate->diffInDays($this->startDate))
                    @php($timeSlotCount = $this->getSlotsInSpan($this->startDate, $this->startDate->endOfDay()))
                    @if($this->getCalendarGrid())
                    <div class="calendar-grid" 
                        style="
                            grid-template-columns: auto repeat({{ $dayCount }}, minmax(var(--width), 1fr));
                            grid-template-rows: auto repeat({{ $timeSlotCount }}, var(--height));
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
                        @for($dayIndex = 0; $dayIndex < $dayCount; $dayIndex++)
                            @php($date = $this->startDate->addDays($dayIndex))
                            <div class="date-header" 
                                style="grid-column: {{ $dayIndex + 2 }}; grid-row: 1;"
                                data-header>
                                {{ $date->format('D, M j') }}
                            </div>
                        @endfor

                        @for($timeSlotIndex = 0; $timeSlotIndex < $timeSlotCount; $timeSlotIndex++)
                            @php($time = $this->startDate->addMinutes($timeSlotIndex * $this->timeSlotWidthInMinutes))
                            <div class="time-label" 
                                style="grid-column: 1; grid-row: {{ ($timeSlotIndex) + 2 }} / span 2; height: calc(var(--height) * 2);"
                                data-time-label>
                                {{ $time->format(__('practice-space::room_availability_calendar.time_format')) }}
                            </div>
                        @endfor

                        <!-- Time Cells -->
                        @for($dayIndex = 0; $dayIndex < $dayCount; $dayIndex++)
                            @for($timeSlotIndex = 0; $timeSlotIndex < $timeSlotCount; $timeSlotIndex++)
                                @php
                                    // Determine cell style based on invalid reason
                                    $is_valid = $this->isSlotValid($this->startDate->addDays($dayIndex), $timeSlotIndex);
                                    $reasonClass = match($is_valid) {
                                        true => '',
                                        'advance_notice' => 'time-cell-advance-notice',
                                        'closing_time' => 'time-cell-closing-time',
                                        'adjacent_booking' => 'time-cell-adjacent-booking',
                                        default => 'time-cell-striped',
                                    };
                                    $tooltip = match($is_valid) {
                                        'advance_notice' => __('practice-space::room_availability_calendar.invalid_reason_advance_notice'),
                                        'closing_time' => __('practice-space::room_availability_calendar.invalid_reason_closing_time'),
                                        'adjacent_booking' => __('practice-space::room_availability_calendar.invalid_reason_adjacent_booking'),
                                        default => __('practice-space::room_availability_calendar.unavailable_time_slot'),
                                    };
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
                                ></div>
                            @endfor
                        @endfor
                    
                        <!-- Bookings -->
                        @foreach($this->getCalendarEvents() as $event)
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
</div>