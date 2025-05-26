<?php

namespace CorvMC\PracticeSpace\Contracts;

use DateTimeImmutable;

interface CalendarEvent
{
    /**
     * Get the unique identifier for this event
     */
    public function getEventId(): string|int;

    /**
     * Get the start time of the event
     */
    public function getStartTime(): DateTimeImmutable;

    /**
     * Get the end time of the event
     */
    public function getEndTime(): DateTimeImmutable;

    /**
     * Get the title/label for this event
     */
    public function getEventTitle(): string;

    /**
     * Get whether this event belongs to the current user
     */
    public function belongsToCurrentUser(): bool;

    /**
     * Get any additional data needed for displaying this event
     *
     * @return array<string, mixed>
     */
    public function getEventMetadata(): array;
}
