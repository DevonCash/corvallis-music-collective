<?php

namespace App\Filament\Admin\Resources\PostResource\Pages;

use App\Filament\Admin\Resources\PostResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPost extends EditRecord
{
    protected static string $resource = PostResource::class;

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
            Actions\Action::make("save")->label("Save")->action("save"),
        ];
    }
}
