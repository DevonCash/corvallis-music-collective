<?php

namespace CorvMC\StateManagement\Example;

use Filament\Forms;
use Illuminate\Database\Eloquent\Model;

/**
 * This is an example concrete state class that extends the BookingState type.
 */
class ConfirmedState extends BookingState
{
    /**
     * The name of the state.
     */
    public static string $name = 'confirmed';
    
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
        return 'Confirmed';
    }
    
    /**
     * Get the color for Filament UI.
     */
    public static function getColor(): string
    {
        return 'success';
    }
    
    /**
     * Get the icon for Filament UI.
     */
    public static function getIcon(): string
    {
        return 'heroicon-o-check-circle';
    }
    
    /**
     * Get the allowed transitions from this state.
     */
    public static function getAllowedTransitions(): array
    {
        return [
            'checked_in' => 'Check in the member',
            'cancelled' => 'Cancel this booking',
            'no_show' => 'Mark as no-show',
        ];
    }
    
    /**
     * Get the form schema for transitioning to this state.
     */
    public static function getForm(): array
    {
        return [
            Forms\Components\Textarea::make('reason')
                ->label('Confirmation Notes')
                ->placeholder('Add any notes about this confirmation')
                ->required(),
                
            Forms\Components\Toggle::make('send_notification')
                ->label('Send confirmation email to member')
                ->default(true),
                
            Forms\Components\DateTimePicker::make('confirmed_at')
                ->label('Confirmation Date/Time')
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
        if ($stateClass === static::class) {
            // Record confirmation time
            if (isset($data['confirmed_at'])) {
                $model->confirmed_at = $data['confirmed_at'];
                $model->save();
            }
            
            // Send notification if requested
            if (isset($data['send_notification']) && $data['send_notification']) {
                // Send email notification to user
                // $model->user->notify(new BookingConfirmedNotification($model));
            }
            
            // Example: Log the transition with your audit system
            // activity()
            //     ->performedOn($model)
            //     ->withProperties([
            //         'reason' => $data['reason'] ?? null,
            //         'confirmed_at' => $data['confirmed_at'] ?? null,
            //         'send_notification' => $data['send_notification'] ?? false,
            //     ])
            //     ->log('Booking confirmed');
        }
        
        return $model;
    }
} 