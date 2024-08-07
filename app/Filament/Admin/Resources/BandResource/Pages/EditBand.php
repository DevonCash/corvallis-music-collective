<?php

namespace App\Filament\Admin\Resources\BandResource\Pages;

use App\Filament\Admin\Resources\BandResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBand extends EditRecord
{
    protected static string $resource = BandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),

            Actions\Action::make("publish")->label(
                fn() => $this->getRecord()->published_at
                    ? "Unpublish"
                    : "Publish"
            ),
        ];
    }
}
