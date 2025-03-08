<?php

namespace CorvMC\PracticeSpace\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum BookingStatus: string implements HasColor, HasIcon, HasLabel
{
    case SCHEDULED = 'scheduled';
    case CONFIRMED = 'confirmed';
    case CHECKED_IN = 'checked_in';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case NO_SHOW = 'no_show';

    public function getColor(): string
    {
        return match($this) {
            self::SCHEDULED => 'warning',
            self::CONFIRMED => 'primary',
            self::CHECKED_IN => 'info',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
            self::NO_SHOW => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match($this) {
            self::SCHEDULED => 'heroicon-o-clock',
            self::CONFIRMED => 'heroicon-o-check-circle',
            self::CHECKED_IN => 'heroicon-o-user-circle',
            self::COMPLETED => 'heroicon-o-flag',
            self::CANCELLED => 'heroicon-o-x-circle',
            self::NO_SHOW => 'heroicon-o-x-mark',
        };
    }

    public function getLabel(): ?string
    {
        return match($this) {
            self::SCHEDULED => 'Scheduled',
            self::CONFIRMED => 'Confirmed',
            self::CHECKED_IN => 'Checked In',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
            self::NO_SHOW => 'No Show',
        };
    }
} 