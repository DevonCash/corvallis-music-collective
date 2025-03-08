<?php

namespace CorvMC\PracticeSpace\Filament\Resources\RoomCategoryResource\Pages;

use CorvMC\PracticeSpace\Filament\Resources\RoomCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRoomCategories extends ListRecords
{
    protected static string $resource = RoomCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
} 