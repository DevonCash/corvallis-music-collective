<?php

namespace CorvMC\PracticeSpace\Filament\Resources\RoomResource\Pages;

use CorvMC\PracticeSpace\Filament\Resources\RoomResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRooms extends ListRecords
{
    protected static string $resource = RoomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
} 