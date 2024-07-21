<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource;
use Filament\Actions;
use App\Models\Event;
use Filament\Resources\Pages\CreateRecord;
use Livewire\Attributes\Url;

class CreateEvent extends CreateRecord
{
    #[Url]
    public ?string $from = null;
    protected static string $resource = EventResource::class;

    public function mount(): void
    {
        parent::mount();
        if (!$this->from) {
            return;
        }

        $original = EventResource::getModel()::find($this->from);
        ray($original);

        $this->form->fill($original->toArray());
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            Actions\Action::make("create-publish")
                ->label("Create & Publish")
                ->color("info")
                ->icon("heroicon-o-eye")
                ->action(function ($record) {
                    $record->publish();
                }),
            $this->getCancelFormAction()->extraAttributes([
                "style" => "display:none",
            ]),
        ];
    }
}
