<?php

namespace CorvMC\Productions\Filament\Resources\ActResource\Pages;

use CorvMC\Productions\Filament\Resources\ActResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAct extends EditRecord
{
    protected static string $resource = ActResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}