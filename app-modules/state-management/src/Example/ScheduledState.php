<?php

namespace CorvMC\StateManagement\Example;

use Filament\Forms;
use Illuminate\Database\Eloquent\Model;

/**
 * This is an example concrete state class that extends the BookingState type.
 */
class ScheduledState extends BookingState
{
    /**
     * The name of the state.
     */
    public static string $name = 'scheduled';
    
    /**
     * Get the name of the state.
     */
    public static function getName(): string
    {
        return static::$name;
    }
    
    /**
     * Get the display name of the state.
     */
    public static function getLabel(): string
    {
        return 'Scheduled';
    }
    
    /**
     * Get the color for Filament UI.
     */
    public static function getColor(): string
    {
        return 'warning';
    }
    
    /**
     * Get the icon for Filament UI.
     */
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
    public static function getForm(): array
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
     * Transition a model to this state.
     */
    public static function transitionTo(Model $model, string $stateClass, array $data = []): Model
    {
        // Call the parent method to handle the basic transition
        $model = parent::transitionTo($model, $stateClass, $data);
        
        // Add state-specific behavior
        if ($stateClass === static::class && isset($data['scheduled_at'])) {
            $model->scheduled_at = $data['scheduled_at'];
            $model->save();
        }
        
        // You could send notifications, update related records, etc.
        // activity()->performedOn($model)
        //     ->withProperties($data)
        //     ->log('Booking scheduled');
        
        return $model;
    }
} 