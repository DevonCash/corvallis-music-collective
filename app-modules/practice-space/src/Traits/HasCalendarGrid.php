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

  public function isSlotValid(CarbonImmutable $dayStart, int $timeIndex, int $slots): string|true
  {
    return true;
  }

  public function getDayIndex(CarbonImmutable $date)
  {
    return $date->diffInDays($this->startDate);
  }

  public function getTimeIndex(CarbonImmutable $dateTime)
  {
    return floor($dateTime->diffInMinutes($this->startDate) / $this->timeSlotWidthInMinutes);
  }

  public function getSlotsInSpan(CarbonImmutable $spanStart, CarbonImmutable $spanEnd)
  {
    return floor($spanEnd->diffInMinutes($spanStart) / $this->timeSlotWidthInMinutes);
  }
}
