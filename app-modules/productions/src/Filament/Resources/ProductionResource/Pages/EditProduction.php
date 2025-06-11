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

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            
            Actions\Action::make('save')
                ->label('Save')
                ->submit('save')
                ->keyBindings(['mod+s'])
                ->color('primary'),

            ProductionState::makeTransitionActionGroup(),
        ];
    }

    protected function getFormActions(): array
    {
        return [];
    }
} 