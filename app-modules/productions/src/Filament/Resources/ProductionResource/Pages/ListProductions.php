<?php

namespace CorvMC\Productions\Filament\Resources\ProductionResource\Pages;

use CorvMC\Productions\Filament\Resources\ProductionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use CorvMC\Productions\Filament\Resources\VenueResource;

class ListProductions extends ListRecords
{
    protected static string $resource = ProductionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('manageVenues')
                ->label('Manage Venues')
                ->icon('heroicon-o-building-office')
                ->url('/admin/productions/venues')
                ->color('gray'),
            Actions\CreateAction::make(),
        ];
    }
} 