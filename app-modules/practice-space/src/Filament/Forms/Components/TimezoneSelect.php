<?php

namespace CorvMC\PracticeSpace\Filament\Forms\Components;

use Filament\Forms\Components\Select;

class TimezoneSelect
{
    public static function make(string $name = 'timezone'): Select
    {
        return Select::make($name)
            ->label('Timezone')
            ->options([
                'UTC' => 'UTC',
                'America/Los_Angeles' => 'Pacific Time (US & Canada)',
                'America/Denver' => 'Mountain Time (US & Canada)',
                'America/Chicago' => 'Central Time (US & Canada)',
                'America/New_York' => 'Eastern Time (US & Canada)',
                'America/Phoenix' => 'Arizona',
                'America/Anchorage' => 'Alaska',
                'America/Adak' => 'Hawaii',
                'America/Vancouver' => 'Pacific Time (Canada)',
                'America/Tijuana' => 'Tijuana',
                'America/Edmonton' => 'Mountain Time (Canada)',
                'America/Chihuahua' => 'Chihuahua',
                'America/Winnipeg' => 'Central Time (Canada)',
                'America/Mexico_City' => 'Mexico City',
                'America/Toronto' => 'Eastern Time (Canada)',
                'America/Halifax' => 'Atlantic Time (Canada)',
                'America/St_Johns' => 'Newfoundland',
                'America/Sao_Paulo' => 'Brasilia',
                'America/Argentina/Buenos_Aires' => 'Buenos Aires',
                'Europe/London' => 'London',
                'Europe/Dublin' => 'Dublin',
                'Europe/Paris' => 'Paris',
                'Europe/Berlin' => 'Berlin',
                'Europe/Rome' => 'Rome',
                'Europe/Madrid' => 'Madrid',
                'Europe/Amsterdam' => 'Amsterdam',
                'Europe/Stockholm' => 'Stockholm',
                'Europe/Athens' => 'Athens',
                'Europe/Moscow' => 'Moscow',
                'Asia/Jerusalem' => 'Jerusalem',
                'Asia/Dubai' => 'Dubai',
                'Asia/Karachi' => 'Karachi',
                'Asia/Kolkata' => 'Mumbai',
                'Asia/Dhaka' => 'Dhaka',
                'Asia/Bangkok' => 'Bangkok',
                'Asia/Singapore' => 'Singapore',
                'Asia/Hong_Kong' => 'Hong Kong',
                'Asia/Shanghai' => 'Beijing',
                'Asia/Seoul' => 'Seoul',
                'Asia/Tokyo' => 'Tokyo',
                'Australia/Perth' => 'Perth',
                'Australia/Adelaide' => 'Adelaide',
                'Australia/Darwin' => 'Darwin',
                'Australia/Brisbane' => 'Brisbane',
                'Australia/Sydney' => 'Sydney',
                'Pacific/Auckland' => 'Auckland',
            ])
            ->searchable()
            ->default(fn() => config('app.timezone'))
            ->helperText('Timezone for this room\'s bookings and availability');
    }
} 