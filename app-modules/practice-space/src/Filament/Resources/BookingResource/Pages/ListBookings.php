<?php

namespace CorvMC\PracticeSpace\Filament\Resources\BookingResource\Pages;

use CorvMC\PracticeSpace\Filament\Resources\BookingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
} 