<?php

namespace CorvMC\PracticeSpace\Traits;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use DateTimeImmutable;

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
        $newStartDate = $this->startDate->subWeek();
        $newEndDate = $this->endDate->addDays(6);
        
        // Get current time in room's timezone
        $now = Carbon::now()->startOfDay();
        
        // Allow navigation to previous week only if it's not entirely in the past
        if ($newEndDate->startOfDay()->greaterThanOrEqualTo($now)) {
            $this->dispatch('dateRangeUpdated', [
                'startDate' => $newStartDate->toDateString(),
                'endDate' => $newEndDate->toDateString()
            ]);
            $this->startDate = $newStartDate;
            $this->endDate = $newEndDate;
        }
    }
    
    /**
     * Move to the next week period
     */
    public function nextPeriod(): void
    {
        // Always use week view
        $newStartDate = $this->startDate->addWeek();
        $newEndDate = $newStartDate->addDays(6);
        
        // Check if the new date range is within the allowable booking window
        if ($this->isDateRangeAllowed($newStartDate, $newEndDate)) {
            $this->dispatch('dateRangeUpdated', [
                'startDate' => $newStartDate->toDateString(),
                'endDate' => $newEndDate->toDateString()
            ]);
            $this->startDate = $newStartDate;
            $this->endDate = $newEndDate;
        }
    }

    /**
     * Move to the current week
     */
    public function today(): void
    {
        // Get the current time in the room's timezone
        $now = CarbonImmutable::now();
        
        // Always use week view starting on Monday
        $newStartDate = $now->startOfWeek(Carbon::MONDAY);
        $newEndDate = $newStartDate->addDays(6); // Sunday
        
        $this->dispatch('dateRangeUpdated', [
            'startDate' => $newStartDate->toDateString(),
            'endDate' => $newEndDate->toDateString()
        ]);
        $this->startDate = $newStartDate;
        $this->endDate = $newEndDate;
    }

    /**
     * Check if navigation to the previous period is allowed
     */
    public function canNavigateToPreviousPeriod(): bool
    {
        // Get the start date of the previous week
        $newStartDate = $this->startDate->subWeek();
        $newEndDate = $newStartDate->addDays(6);
        
        // Get current time in room's timezone
        $now = Carbon::now()->startOfDay();
        
        // Ensure comparison is in the same timezone
        return $newEndDate->startOfDay()->greaterThanOrEqualTo($now);
    }
    
    /**
     * Check if navigation to the next period is allowed
     */
    public function canNavigateToNextPeriod(): bool
    {
        $newStartDate = $this->startDate->copy()->addWeek();
        $newEndDate = $newStartDate->copy()->addDays(6);
        return $this->isDateRangeAllowed($newStartDate, $newEndDate);
    }

    /**
     * Check if the given date range is within the allowable booking window
     */
    protected function isDateRangeAllowed(CarbonImmutable $startDate, CarbonImmutable $endDate): bool
    {
       return true;
    }

} 