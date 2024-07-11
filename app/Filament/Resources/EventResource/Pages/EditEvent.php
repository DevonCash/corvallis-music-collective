<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEvent extends EditRecord
{
    protected static string $resource = EventResource::class;

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()->extraAttributes([
                "style" => "display:none",
            ]),
            $this->getCancelFormAction()->extraAttributes([
                "style" => "display:none",
            ]),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make("publish")
                ->label(
                    fn($record) => $record->isPublished()
                        ? "Unpublish"
                        : "Publish"
                )
                ->color("info")
                ->icon(
                    fn($record) => $record->isPublished()
                        ? "heroicon-o-eye-slash"
                        : "heroicon-o-eye"
                )
                ->action(function ($record) {
                    if ($record->isPublished()) {
                        $record->unpublish();
                    } else {
                        $record->publish();
                    }
                }),
            Actions\Action::make("save")->label("Save changes")->action("save"),
        ];
    }
}
