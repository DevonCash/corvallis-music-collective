<?php

namespace CorvMC\Productions\Filament\Resources\ProductionResource\Pages;

use CorvMC\Productions\Filament\Resources\ProductionResource;
use CorvMC\Productions\Models\Production;
use CorvMC\Productions\Models\States\PlanningState;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateProduction extends CreateRecord
{
    protected static string $resource = ProductionResource::class;

    /**
     * Override mount to bypass the create form and go directly to edit
     */
    public function mount(): void
    {
        // Don't call parent::mount() to skip form initialization
        
        // Create production with minimal required data
        $production = $this->createProductionWithDefaults();
        
        // Redirect to edit page
        $this->redirect(static::getResource()::getUrl('edit', ['record' => $production]));
    }

    /**
     * Create a new production with sensible defaults
     */
    protected function createProductionWithDefaults(): Production
    {
        $counter = $this->getNextProductionCounter();
        
        return Production::create([
            'title' => "New Production #{$counter}",
            'description' => null,
            'venue_id' => null,
            'start_date' => now()->addWeeks(2)->setTime(19, 0), // Default to 7 PM, 2 weeks from now
            'end_date' => now()->addWeeks(2)->setTime(22, 0),   // Default to 10 PM same day
            'status' => PlanningState::getName(),
            'capacity' => null,
            'poster' => null,
            'ticket_link' => null,
            'wrap_up_data' => null,
        ]);
    }

    /**
     * Get the next production counter for unique naming
     */
    protected function getNextProductionCounter(): int
    {
        $latestProduction = Production::where('title', 'like', 'New Production #%')
            ->orderByDesc('id')
            ->first();

        if (!$latestProduction) {
            return 1;
        }

        // Extract number from title like "New Production #5"
        preg_match('/New Production #(\d+)/', $latestProduction->title, $matches);
        
        return isset($matches[1]) ? (int)$matches[1] + 1 : Production::count() + 1;
    }

    /**
     * Override getTitle to provide a better page title
     */
    public function getTitle(): string
    {
        return 'Creating New Production...';
    }

    /**
     * Override render to show a simple loading state
     */
    public function render(): \Illuminate\Contracts\View\View
    {
        return view('filament::pages.simple-page', [
            'title' => $this->getTitle(),
        ]);
    }
} 