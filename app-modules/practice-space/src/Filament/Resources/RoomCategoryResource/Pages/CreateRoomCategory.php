<?php

namespace CorvMC\PracticeSpace\Filament\Resources\RoomCategoryResource\Pages;

use CorvMC\PracticeSpace\Filament\Resources\RoomCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateRoomCategory extends CreateRecord
{
    protected static string $resource = RoomCategoryResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
} 