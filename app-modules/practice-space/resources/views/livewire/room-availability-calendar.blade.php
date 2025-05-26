<div id="room-availability-calendar-{{ $this->getId() }}" wire:id="{{ $this->getId() }}">
    <!-- Main Calendar Container -->
    <!-- Calendar Container -->
    <div class="calendar-container">
        <!-- Room Selection and Date Navigation -->
        <div class="calendar-header">
            <div class="flex flex-1 md:flex-col justify-between gap-2 items-center md:items-start w-full">
                <div class='calendar-header-title'>
                    <div>{{ $this->startDate->format('M j') }} - {{ $this->endDate->format('M j, Y') }}</div>
                </div>
                <div class="flex gap-1">
                    <button wire:click="previousPeriod" class="calendar-nav-button"
                        @if (!$this->canNavigateToPreviousPeriod()) disabled @endif>
                        <x-heroicon-s-chevron-left class="w-5 h-5" />
                    </button>
                    <button wire:click="nextPeriod" class="calendar-nav-button"
                        @if (!$this->canNavigateToNextPeriod()) disabled @endif>
                        <x-heroicon-s-chevron-right class="w-5 h-5" />
                    </button>
                    <button wire:click="today" class="calendar-nav-button">
                        {{ __('practice-space::room_availability_calendar.today') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Scroll Container -->
        <div class="overflow-x-auto relative" style="--width: 10rem; --height: 2rem; overscroll-behavior-x: none;"
            x-data="{ hasScroll: false, scrollLeft: 0, scrollRight: false }" x-init="">
            <!-- Calendar Container -->
            <div class="w-fit" wire:replace>
                <!-- Calendar Grid -->
                @php($dayCount = $this->startDate->diffInDays($this->endDate) + 1)
                @php($timeSlotCount = $this->getSlotsInSpan($this->getDayStart($this->startDate), $this->getDayEnd($this->startDate)))
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
                            const cell = Array.from(document.elementsFromPoint(event.clientX, event.clientY)).find(x => x.classList.contains('time-cell'));
                            if (!cell) return;

                            if (cell.hasAttribute('data-invalid')) {
                                this.showCursor = false;
                                return;
                            }

                            this.showCursor = true;
                            this.cursorColumn = getComputedStyle(cell).gridColumnStart;
                            this.cursorRow = getComputedStyle(cell).gridRowStart;
                        },

                        handleMouseLeave() {
                            this.showCursor = false;
                        },

                        openBookingForm(ev) {
                            const booking = ev.target.closest('[data-booking]')
                            if (booking) {
                                $wire.replaceMountedAction('manageBooking', { booking_id: booking.dataset.booking });
                                ev.stopPropagation()
                                return;
                            }

                            if (!this.showCursor) return;
                            const opts = {
                                'room_id': parseInt(ev.target.dataset.roomId),
                                'booking_date': ev.target.dataset.date,
                                'booking_time': ev.target.dataset.time,
                            };
                            $wire.mountAction('createBooking', opts);
                        }
                    }" @mousemove="handleMouseMove" @mouseleave="handleMouseLeave"
                    @click="openBookingForm($event);">
                    <!-- Corner Cell (Time) -->
                    <div class="time-column-header" style="grid-column: 1; grid-row: 1; " data-header>
                        {{ __('practice-space::room_availability_calendar.time') }}
                    </div>

                    @for ($timeSlotIndex = 0; $timeSlotIndex < $timeSlotCount; $timeSlotIndex += 2)
                        @php($time = $this->getDayStart($this->startDate)->addMinutes($timeSlotIndex * $this->timeSlotWidthInMinutes))
                        <div class="time-label"
                            style="grid-column: 1; grid-row: {{ $timeSlotIndex + 2 }} / span 2; height: calc(var(--height) * 2);"
                            data-time-label>
                            {{ $time->format(__('practice-space::room_availability_calendar.time_format')) }}
                        </div>
                    @endfor

                    <!-- Time Cells -->
                    @for ($dayIndex = 0; $dayIndex < $dayCount; $dayIndex++)
                        @php($date = $this->startDate->addDays($dayIndex))
                        <div class="date-header" style="grid-column: {{ $dayIndex + 2 }}; grid-row: 1;"
                            data-date="{{ $date->format('Y-m-d') }}" data-room-id="{{ $this->room->id }}" data-header>
                            {{ $date->format('D, M j') }}
                        </div>
                        @php($valid = $this->room->getValidSlots($this->startDate->addDays($dayIndex)))
                        @for ($timeSlotIndex = 0; $timeSlotIndex < $timeSlotCount; $timeSlotIndex++)
                            @php($is_valid = $valid[$timeSlotIndex])
                            @php(
    $reasonClass = match ($is_valid) {
        true => '',
        'advance_notice' => 'time-cell-advance-notice',
        'closing_time' => 'time-cell-closing-time',
        'adjacent_booking' => 'time-cell-adjacent-booking',
        'time_in_past' => 'time-cell-past',
        default => 'time-cell-striped',
    })
    @php(
    $tooltip = match ($is_valid) {
        'advance_notice' => __('practice-space::room_availability_calendar.invalid_reason_advance_notice'),
        'closing_time' => __('practice-space::room_availability_calendar.invalid_reason_closing_time'),
        'adjacent_booking' => __('practice-space::room_availability_calendar.invalid_reason_adjacent_booking'),
        default => __('practice-space::room_availability_calendar.unavailable_time_slot'),
    }
    )
                            @php($date = $this->startDate->addDays($dayIndex))
                            @php($time = $this->getDayStart($date)->addMinutes($timeSlotIndex * $this->timeSlotWidthInMinutes))
                            <div class="time-cell {{ $is_valid === true ? '' : $is_valid }}"
                                style="grid-column: {{ $dayIndex + 2 }}; grid-row: {{ $timeSlotIndex + 2 }};"
                                data-date="{{ $date->format('Y-m-d') }}" data-time="{{ $time->format('H:i') }}"
                                data-room-id="{{ $this->room->id }}" {{ $is_valid === true ? '' : 'data-invalid' }}
                                title="{{ $time->format('g:i a') .
                                    ' (' .
                                    ($is_valid === true
                                        ? __('practice-space::room_availability_calendar.available_time_slot')
                                        : __("practice-space::room_availability_calendar.{$is_valid}")) .
                                    ')' }}">
                            </div>
                        @endfor
                    @endfor

                    <!-- Bookings -->
                    @foreach ($this->getCalendarEvents() as $event)
                        @continue(Auth::user()->id !== $event->user_id)
                        @php($date_index = $this->getDayIndex($event->getStartTime()))
                        @php($time_index = $this->getTimeIndex($event->getStartTime()))
                        @php($span = $this->getSlotsInSpan($event->getStartTime(), $event->getEndTime()))
                        <div data-booking="{{ $event->id }}"
                            class="booking  {{ $event->user === Auth::id() ? 'booking-by-user' : 'booking-by-other' }}"
                            style="
                                    grid-column: {{ $date_index + 2 }};
                                    grid-row: {{ $time_index + 2 }} / span {{ $span }};
                                ">
                            <div class="booking-title">{{ $event->getEventTitle() }}</div>
                            <div>{{ $event->getStartTime()->format('g:i a') }} -
                                {{ $event->getEndTime()->format('g:i a') }}</div>
                        </div>
                    @endforeach

                    <!-- Cursor Element -->
                    <div x-show="showCursor" x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                        class="calendar-cursor"
                        :style="`
                                                    grid-column: ${cursorColumn};
                                                    grid-row: ${cursorRow};
                                                    top: -1px;
                                                    left: -1px;
                                                    width: calc(100% + 1px);
                                                    height: calc(100% + 1px);
                                                `">
                        <div class="calendar-cursor-icon">
                            <x-filament::icon icon='heroicon-m-plus' class='size-5' />
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <x-filament-actions::modals />
</div>
