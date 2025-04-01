<?php

namespace CorvMC\PracticeSpace\Livewire;

use Carbon\Carbon;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Livewire\Component;
use CorvMC\PracticeSpace\Models\Room;
use CorvMC\PracticeSpace\Filament\Actions\CreateBookingAction;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use CorvMC\PracticeSpace\Traits\HasCalendarGrid;
use CorvMC\PracticeSpace\Traits\HasCalendarPaging;
use CorvMC\PracticeSpace\Traits\HasBookings;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

class RoomAvailabilityCalendar extends Component implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;
    use HasCalendarGrid;
    use HasCalendarPaging;

    /**
     * The room being displayed in the calendar
     */
    public Room $room;

    public function mount(Room $room): void
    {
        $this->room = $room;

        // Set initial date range to current week, always starting on Monday
        $this->startDate = CarbonImmutable::now()->startOfWeek(\Carbon\CarbonInterface::MONDAY);
        $this->endDate = $this->startDate->copy()->addDays(6); // Sunday
    }

    public function getDayStart(CarbonImmutable $date): CarbonImmutable
    {
        return $this->room->booking_policy->getOpeningTime($date);
    }

    public function getDayEnd(CarbonImmutable $date): CarbonImmutable
    {
        return $this->room->booking_policy->getClosingTime($date);
    }

    public function getCalendarEvents(): Collection
    {
        return $this->room
            ->bookings()
            ->where("start_time", ">=", $this->startDate)
            ->where("end_time", "<=", $this->endDate)
            ->get();
    }

    /**
     * Format the time range for display
     */
    private function formatTimeRange(Carbon $startTime, Carbon $endTime): string
    {
        $startFormatted = $startTime->format(
            __("practice-space::room_availability_calendar.time_format")
        );
        $endFormatted = $endTime->format(
            __("practice-space::room_availability_calendar.time_format")
        );

        return __(
            "practice-space::room_availability_calendar.time_range_format",
            [
                "start_time" => $startFormatted,
                "end_time" => $endFormatted,
            ]
        );
    }

    public function render()
    {
        return view("practice-space::livewire.room-availability-calendar");
    }

    public function createBookingAction()
    {
        return CreateBookingAction::make()
        ->label(
            __("practice-space::room_availability_calendar.create_booking")
        );
    }
}
