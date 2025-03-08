# State Management Module

A powerful, flexible state management system for Laravel models with seamless Filament integration.

## Overview

This module provides a class-based state management system for Laravel models, allowing each state to be represented by its own class with specific behavior, transitions, and UI components. The system is designed to be:

- **Self-contained**: Each state defines its own transitions, form schema, and UI components
- **Flexible**: States can collect different data through custom forms during transitions
- **Compatible**: Works with your existing audit-log system for tracking state changes
- **Filament-integrated**: Seamless integration with Filament UI components

## Core Concepts

### State Hierarchy

The module uses a three-level hierarchy:

1. **AbstractState**: The base abstract class that implements the StateInterface
2. **State Type**: A custom abstract class that extends AbstractState for a specific model (e.g., BookingState)
3. **Concrete States**: Individual state classes that extend the State Type (e.g., ScheduledState, ConfirmedState)

This hierarchy allows for type-specific behavior while maintaining a clean inheritance structure.

### State Names

Each concrete state class defines its own static `$name` property, which is the string value stored in the database:

```php
class ScheduledState extends BookingState
{
    public static string $name = 'scheduled';
    
    public static function getName(): string
    {
        return static::$name;
    }
    
    // ...
}
```

### State Casting

The module leverages Laravel's native casting system to cast state columns to state objects:

```php
protected $casts = [
    'state' => BookingState::class,
];
```

This allows you to access state methods directly:

```php
$booking->state->getLabel();
$booking->state->transitionTo('confirmed');
```

### State Transitions

Transitions between states:

1. Are defined within each state class as string keys
2. Can collect custom data through Filament forms
3. Are validated to ensure only allowed transitions occur
4. Trigger state-specific behavior when executed
5. Can be integrated with your existing audit-log system

### Filament Integration

The module provides deep integration with Filament:

1. Custom form schemas for each state transition
2. Table actions for triggering state transitions
3. Infolist sections for displaying state information
4. Badge components for visualizing states

## Implementation Requirements

### Base Interfaces and Classes

```php
// StateInterface - Core interface for all states
interface StateInterface
{
    public static function getName(): string;
    public static function getLabel(): string;
    public static function getColor(): string;
    public static function getIcon(): string;
    public static function getAllowedTransitions(): array;
    public static function getFormSchema(): array;
    public function canTransitionTo(string $stateName): bool;
    public function transitionTo(string $stateName, array $data = []): Model;
}

// AbstractState - Base implementation for states
abstract class AbstractState implements StateInterface
{
    // Common implementation for all states
    protected $model;
    
    public function __construct(Model $model)
    {
        $this->model = $model;
    }
    
    // Default implementations of interface methods
}

// State Type - Custom abstract class for a specific model
abstract class BookingState extends AbstractState
{
    // List of all available states
    protected static array $states = [
        'scheduled' => ScheduledState::class,
        'confirmed' => ConfirmedState::class,
        // ...
    ];
    
    // Helper methods for resolving state classes
    public static function resolveStateClass(string $state): string
    {
        return static::$states[$state] ?? static::$states['scheduled'];
    }
    
    // Cast method for Laravel
    public static function castUsing(array $arguments)
    {
        return new class extends CastsAttributes {
            public function get($model, $key, $value, $attributes)
            {
                $stateClass = BookingState::resolveStateClass($value);
                return new $stateClass($model);
            }
            
            public function set($model, $key, $value, $attributes)
            {
                if ($value instanceof BookingState) {
                    return ['state' => $value::getName()];
                }
                
                return ['state' => $value];
            }
        };
    }
}
```

### Model Requirements

Models using states should:

1. Cast the state column to the State Type class

```php
class Booking extends Model
{
    protected $casts = [
        'state' => BookingState::class,
    ];
}
```

### Database Structure

The module requires:

1. A `state` column on the model table (storing the state name as a string)

## Usage Examples

### Creating a State Type

```php
abstract class BookingState extends AbstractState
{
    // List of all available states
    protected static array $states = [
        'scheduled' => ScheduledState::class,
        'confirmed' => ConfirmedState::class,
        'checked_in' => CheckedInState::class,
        'completed' => CompletedState::class,
        'cancelled' => CancelledState::class,
        'no_show' => NoShowState::class,
    ];
    
    // Helper methods for resolving state classes
    public static function resolveStateClass(string $state): string
    {
        return static::$states[$state] ?? static::$states['scheduled'];
    }
    
    public static function getAvailableStates(): array
    {
        return static::$states;
    }
    
    // Cast method for Laravel
    public static function castUsing(array $arguments)
    {
        return new class extends CastsAttributes {
            public function get($model, $key, $value, $attributes)
            {
                if (!$value || !isset(BookingState::getAvailableStates()[$value])) {
                    $value = 'scheduled';
                }
                
                $stateClass = BookingState::resolveStateClass($value);
                return new $stateClass($model);
            }
            
            public function set($model, $key, $value, $attributes)
            {
                if ($value instanceof BookingState) {
                    return ['state' => $value::getName()];
                }
                
                return ['state' => $value];
            }
        };
    }
}
```

### Creating Concrete States

```php
class ScheduledState extends BookingState
{
    public static string $name = 'scheduled';
    
    public static function getName(): string
    {
        return static::$name;
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
    
    public static function getAllowedTransitions(): array
    {
        return [
            'confirmed' => 'Confirm this booking',
            'cancelled' => 'Cancel this booking',
        ];
    }
    
    public static function getFormSchema(): array
    {
        return [
            Forms\Components\Textarea::make('reason')
                ->label('Scheduling Notes')
                ->required(),
        ];
    }
    
    protected function afterTransition(array $data = []): void
    {
        // State-specific behavior
        
        // Example: Log the transition with your audit system
        // activity()->performedOn($this->model)
        //     ->withProperties($data)
        //     ->log('Booking scheduled');
    }
}
```

### Using States in Models

```php
class Booking extends Model
{
    protected $fillable = [
        'user_id',
        'room_id',
        'start_time',
        'end_time',
        'state',
    ];
    
    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'state' => BookingState::class,
    ];
}
```

### Transitioning States

```php
// Simple transition
$booking->state->transitionTo('confirmed');

// Transition with data
$booking->state->transitionTo('checked_in', [
    'reason' => 'Member arrived on time',
    'checked_in_by' => $staffMember->id,
]);

// Check if transition is allowed
if ($booking->state->canTransitionTo('completed')) {
    $booking->state->transitionTo('completed');
}
```

### Filament Integration

```php
// In a Filament resource
public static function table(Table $table): Table
{
    return $table
        ->columns([
            // Other columns
            Tables\Columns\TextColumn::make('state')
                ->label('Status')
                ->formatStateUsing(fn ($state) => $state->getLabel())
                ->badge()
                ->color(fn ($state) => $state->getColor())
                ->icon(fn ($state) => $state->getIcon()),
        ])
        ->actions([
            // Use the dynamic state transition actions
            fn (Booking $record) => $record->state->getStateTransitionActions(),
        ]);
}
```

### Integration with Audit Logging

You can integrate with your existing audit-log system in the `afterTransition` method:

```php
protected function afterTransition(array $data = []): void
{
    // Using Spatie Activity Log
    activity()
        ->performedOn($this->model)
        ->withProperties([
            'from' => static::getName(),
            'to' => $data['to_state'] ?? null,
            'reason' => $data['reason'] ?? null,
            'data' => $data,
        ])
        ->log('State changed to ' . static::getLabel());
}
```

## Design Principles

1. **Single Responsibility**: Each state class is responsible for its own behavior
2. **Open/Closed**: The system is open for extension but closed for modification
3. **Encapsulation**: States encapsulate their behavior and transitions
4. **Self-Documentation**: The code structure makes the state machine clear
5. **Consistency**: UI and behavior are consistent across the application

## Benefits

1. **Type Safety**: Strong typing for better IDE support and fewer bugs
2. **Clean API**: Access state methods directly through Laravel's casting system
3. **Maintainability**: Each state is isolated, making changes safer
4. **Flexibility**: Easy to add new states or transitions
5. **Rich UI**: Seamless integration with Filament
6. **Validation**: Built-in validation of state transitions
7. **Compatibility**: Works with your existing audit-log system 