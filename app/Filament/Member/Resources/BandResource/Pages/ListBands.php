<?php

namespace App\Filament\Member\Resources\BandResource\Pages;

use App\Filament\Member\Resources\BandResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBands extends ListRecords
{
    protected static string $resource = BandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
