<?php

namespace CorvMC\Productions\Filament\Resources\VenueResource\Pages;

use CorvMC\Productions\Filament\Resources\VenueResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\Concerns\UsesResourceForm;

class ListVenues extends ListRecords
{
    protected static string $resource = VenueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
    
}
