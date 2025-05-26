<?php

namespace CorvMC\CommunityCalendar\Filament\Resources\CommunityEventResource\Pages;

use CorvMC\CommunityCalendar\Filament\Resources\CommunityEventResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListCommunityEvents extends ListRecords
{
    protected static string $resource = CommunityEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('createCommunityEvent')
                ->label('New Event')
                ->action(function () {
                    $model = CommunityEventResource::getModel();
                    $event = new $model();
                    $event->user_id = Auth::id();
                    $event->save();

                    $this->redirect(CommunityEventResource::getUrl('edit', ['record' => $event]));
                })
        ];
    }
}
