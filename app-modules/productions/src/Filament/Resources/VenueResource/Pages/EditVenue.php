<?php

namespace CorvMC\Productions\Filament\Resources\VenueResource\Pages;

use CorvMC\Productions\Filament\Resources\VenueResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVenue extends EditRecord
{
    protected static string $resource = VenueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
} 