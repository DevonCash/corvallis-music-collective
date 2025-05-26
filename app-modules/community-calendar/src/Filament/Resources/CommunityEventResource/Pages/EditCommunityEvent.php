<?php

namespace CorvMC\CommunityCalendar\Filament\Resources\CommunityEventResource\Pages;

use CorvMC\CommunityCalendar\Filament\Resources\CommunityEventResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;

class EditCommunityEvent extends EditRecord
{
    protected static string $resource = CommunityEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    #[On('navigating')]
    public function handleNavigation($path)
    {
        Log::info('Navigating to: ' . $path);
        return;

        // This will be called when Livewire navigation is about to happen
        $currentData = $this->getRecord()->toArray();
        $hasChanges = $this->hasModelChanged($currentData);

        // If no changes and this is a newly created record
        if (!$hasChanges && $this->getRecord()->wasRecentlyCreated) {
            // Delete the unused model
            $this->getRecord()->delete();
        }
    }
}
