<?php

namespace CorvMC\PracticeSpace\Traits;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

trait HasCalendarGrid
{
    public CarbonImmutable $startDate;
    public CarbonImmutable $endDate;

    public int $timeSlotWidthInMinutes = 30;

    protected function getCalendarEvents(): Collection
    {
        return collect();
    }

    public function getDayStart(CarbonImmutable $date)
    {
        return $date->startOfDay();
    }

    public function getDayEnd(CarbonImmutable $date)
    {
        return $date->endOfDay();
    }

    public function isSlotValid(
        CarbonImmutable $dayStart,
        int $timeIndex,
        int $slots
    ): string|true {
        return true;
    }

    public function getDayIndex(CarbonImmutable $date)
    {
        return floor($this->startDate->diffInDays($date));
    }

    public function getTimeIndex(CarbonImmutable $dateTime)
    {
        $dayStart = $this->getDayStart($dateTime);
        return floor(
            $dayStart->diffInMinutes($dateTime) / $this->timeSlotWidthInMinutes
        );
    }

    public function getSlotsInSpan(
        CarbonImmutable $spanStart,
        CarbonImmutable $spanEnd
    ) {
        return abs(
            floor(
                $spanEnd->diffInMinutes($spanStart) /
                    $this->timeSlotWidthInMinutes
            )
        );
    }
}
