<?php

namespace App\Filament\Admin\Resources\VenueResource\Pages;

use App\Filament\Admin\Resources\VenueResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageVenues extends ManageRecords
{
    protected static string $resource = VenueResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
