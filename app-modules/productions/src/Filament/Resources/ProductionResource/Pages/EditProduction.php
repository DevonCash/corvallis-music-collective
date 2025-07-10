<?php

namespace CorvMC\Productions\Filament\Resources\ProductionResource\Pages;

use CorvMC\Productions\Filament\Resources\ProductionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use CorvMC\Productions\Models\States\ProductionState;

class EditProduction extends EditRecord
{
    protected static string $resource = ProductionResource::class;

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            ProductionState::makeTransitionActionGroup(),
        ];
    }
} 