<?php

namespace CorvMC\PracticeSpace\Filament\Resources\RoomResource\Pages;

use CorvMC\PracticeSpace\Filament\Resources\RoomResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateRoom extends CreateRecord
{
    protected static string $resource = RoomResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
} 