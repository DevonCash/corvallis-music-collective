<?php

namespace CorvMC\PracticeSpace\Filament\Resources\BookingResource\Pages;

use CorvMC\PracticeSpace\Filament\Resources\BookingResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBooking extends CreateRecord
{
    protected static string $resource = BookingResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
} 