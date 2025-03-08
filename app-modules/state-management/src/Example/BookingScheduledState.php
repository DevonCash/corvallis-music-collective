<?php

namespace CorvMC\StateManagement\Example;

use CorvMC\StateManagement\AbstractState;
use Filament\Forms;

/**
 * This is an example state implementation to demonstrate how to create a state.
 */
class BookingScheduledState extends AbstractState
{
    public static function getName(): string
    {
        return 'scheduled';
    }
    
    public static function getLabel(): string
    {
        return 'Scheduled';
    }
    
    public static function getColor(): string
    {
        return 'warning';
    }
    
    public static function getIcon(): string
    {
        return 'heroicon-o-clock';
    }
    
    /**
     * Get the allowed transitions from this state.
     */
    public static function getAllowedTransitions(): array
    {
        return [
            'confirmed' => 'Confirm this booking',
            'cancelled' => 'Cancel this booking',
        ];
    }
    
    /**
     * Get the form schema for transitioning to this state.
     */
    public static function getFormSchema(): array
    {
        return [
            Forms\Components\Textarea::make('reason')
                ->label('Scheduling Notes')
                ->placeholder('Add any notes about this scheduling')
                ->required(),
                
            Forms\Components\DateTimePicker::make('scheduled_at')
                ->label('Scheduled Date/Time')
                ->default(now())
                ->required(),
        ];
    }
    
    /**
     * Get the model class for this state.
     */
    protected static function getModelClass(): string
    {
        return BookingModel::class;
    }
    
    /**
     * Perform actions after entering the state.
     */
    protected function afterEnter($model, array $data = []): void
    {
        // Example of state-specific behavior
        if (isset($data['scheduled_at'])) {
            $model->scheduled_at = $data['scheduled_at'];
            $model->save();
        }
        
        // You could send notifications, update related records, etc.
    }
} 