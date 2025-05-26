<?php

namespace CorvMC\PracticeSpace\Traits;

use Carbon\Carbon;
use Carbon\CarbonImmutable;

trait HasCalendarPaging
{
    public CarbonImmutable $startDate;
    public CarbonImmutable $endDate;

    /**
     * Move to the previous week period
     */
    public function previousPeriod(): void
    {
        // Always use week view
        $this->startDate = $this->startDate->subWeek();
        $this->endDate = $this->startDate->endOfWeek();
    }

    /**
     * Move to the next week period
     */
    public function nextPeriod(): void
    {
        // Always use week view
        $this->startDate = $this->startDate->addWeek();
        $this->endDate = $this->startDate->endOfWeek();
    }

    /**
     * Move to the current week
     */
    public function today(): void
    {
        // Get the current time in the room's timezone
        $now = CarbonImmutable::now();

        $this->startDate = CarbonImmutable::now()->startOfWeek();
        $this->endDate = $this->startDate->endOfWeek();
    }

    /**
     * Check if navigation to the previous period is allowed
     */
    public function canNavigateToPreviousPeriod(): bool
    {
        $newEndDate = $this->endDate->subWeek();
        $now = Carbon::now()->startOfDay();
        return $newEndDate->startOfDay()->greaterThanOrEqualTo($now);
    }

    /**
     * Check if navigation to the next period is allowed
     */
    public function canNavigateToNextPeriod(): bool
    {
        return true;
    }
}
