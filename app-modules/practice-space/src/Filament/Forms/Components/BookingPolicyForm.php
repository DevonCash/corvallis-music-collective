<?php

namespace CorvMC\PracticeSpace\Filament\Forms\Components;

use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;

class BookingPolicyForm 
{
    protected string $view = 'filament-forms::components.grid';

    public static function make(): Grid
    {
        return  Grid::make()
        ->schema([
            Section::make('Operating Hours')
                ->description('Set the opening and closing times for bookings')
                ->icon('heroicon-o-clock')
                ->collapsible()
                ->schema([
                    Grid::make()
                        ->schema([
                            TimePicker::make('booking_policy.opening_time')
                                ->label('Opening Time')
                                ->seconds(false)
                                ->required()
                                ->default('08:00')
                                ->format('H:i')
                                ->placeholder('08:00')
                                ->helperText('The time when bookings can start (24-hour format)')
                                ->validationMessages([
                                    'required' => 'Please set an opening time',
                                    'regex' => 'Please use a valid 24-hour time format (HH:MM)',
                                ])
                                ->columnSpan(['md' => 3]),
                            
                            TimePicker::make('booking_policy.closing_time')
                                ->label('Closing Time')
                                ->seconds(false)
                                ->required()
                                ->default('22:00')
                                ->format('H:i')
                                ->placeholder('22:00')
                                ->helperText('The time when bookings must end (24-hour format)')
                                ->validationMessages([
                                    'required' => 'Please set a closing time',
                                    'regex' => 'Please use a valid 24-hour time format (HH:MM)',
                                ])
                                ->columnSpan(['md' => 3]),
                        ])
                        ->columns(6),
                ])
                ->columnSpan(['md' => 1]),

            Section::make('Booking Duration')
                ->description('Set the minimum and maximum booking duration')
                ->icon('heroicon-o-arrow-path')
                ->collapsible()
                ->schema([
                    Grid::make()
                        ->schema([
                            TextInput::make('booking_policy.min_booking_duration_hours')
                                ->label('Minimum Duration')
                                ->required()
                                ->numeric()
                                ->default(0.5)
                                ->minValue(0.1)
                                ->step(0.1)
                                ->suffix('hours')
                                ->placeholder('0.5')
                                ->helperText('Minimum booking duration (e.g., 0.5 for 30 minutes)')
                                ->validationMessages([
                                    'required' => 'Please set a minimum duration',
                                    'numeric' => 'Duration must be a number',
                                    'min' => 'Duration must be at least 0.1 hours (6 minutes)',
                                ])
                                ->columnSpan(['md' => 3]),
                            
                            TextInput::make('booking_policy.max_booking_duration_hours')
                                ->label('Maximum Duration')
                                ->required()
                                ->numeric()
                                ->default(8.0)
                                ->minValue(0.5)
                                ->step(0.5)
                                ->suffix('hours')
                                ->placeholder('8.0')
                                ->helperText('Maximum booking duration')
                                ->validationMessages([
                                    'required' => 'Please set a maximum duration',
                                    'numeric' => 'Duration must be a number',
                                    'min' => 'Duration must be at least 0.5 hours',
                                ])
                                ->rules([
                                    fn (Forms\Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                        $minDuration = (float) $get('booking_policy.min_booking_duration_hours');
                                        if ($value < $minDuration) {
                                            $fail("Maximum duration must be greater than or equal to the minimum duration ({$minDuration} hours)");
                                        }
                                    },
                                ])
                                ->columnSpan(['md' => 3]),
                        ])
                        ->columns(6),
                ])
                ->columnSpan(['md' => 1]),
        
    Grid::make()
        ->schema([
            Section::make('Advance Booking')
                ->description('Set how far in advance bookings can be made')
                ->icon('heroicon-o-calendar')
                ->collapsible()
                ->schema([
                    Grid::make()
                        ->schema([
                            TextInput::make('booking_policy.min_advance_booking_hours')
                                ->label('Minimum Advance Notice')
                                ->required()
                                ->numeric()
                                ->default(1.0)
                                ->minValue(0)
                                ->step(0.5)
                                ->suffix('hours')
                                ->helperText('Minimum hours in advance a booking must be made')
                                ->columnSpan(['md' => 3]),
                            
                            TextInput::make('booking_policy.max_advance_booking_days')
                                ->label('Maximum Advance Booking')
                                ->required()
                                ->numeric()
                                ->default(90)
                                ->minValue(1)
                                ->step(1)
                                ->suffix('days')
                                ->helperText('Maximum days in advance a booking can be made')
                                ->columnSpan(['md' => 3]),
                        ])
                        ->columns(6),
                ])
                ->columnSpan(['md' => 1]),

            Section::make('Other Policies')
                ->description('Set cancellation policy and booking limits')
                ->icon('heroicon-o-cog-6-tooth')
                ->collapsible()
                ->schema([
                    Grid::make()
                        ->schema([
                            TextInput::make('booking_policy.cancellation_hours')
                                ->label('Cancellation Notice')
                                ->required()
                                ->numeric()
                                ->default(24)
                                ->minValue(0)
                                ->step(1)
                                ->suffix('hours')
                                ->helperText('Hours before start time when cancellation with refund is allowed')
                                ->columnSpan(['md' => 3]),
                            
                            TextInput::make('booking_policy.max_bookings_per_week')
                                ->label('Maximum Bookings Per Week')
                                ->required()
                                ->numeric()
                                ->default(5)
                                ->minValue(0)
                                ->step(1)
                                ->helperText('Maximum bookings per user per week (0 for unlimited)')
                                ->columnSpan(['md' => 3]),
                        ])
                        ->columns(6),
                ])
                ->columnSpan(['md' => 1]),
        ])
        ->columns(['md' => 2])
        ->columnSpanFull()
        ->dehydrateStateUsing(function ($state) {
          if (isset($state['booking_policy'])) {
              // Convert camelCase to snake_case for BookingPolicy::fromArray
              return [
                  'opening_time' => $state['booking_policy']['opening_time'] ?? '08:00',
                  'closing_time' => $state['booking_policy']['closing_time'] ?? '22:00',
                  'min_booking_duration_hours' => (float)($state['booking_policy']['min_booking_duration_hours'] ?? 0.5),
                  'max_booking_duration_hours' => (float)($state['booking_policy']['max_booking_duration_hours'] ?? 8.0),
                  'min_advance_booking_hours' => (float)($state['booking_policy']['min_advance_booking_hours'] ?? 1.0),
                  'max_advance_booking_days' => (int)($state['booking_policy']['max_advance_booking_days'] ?? 90),
                  'cancellation_hours' => (int)($state['booking_policy']['cancellation_hours'] ?? 24),
                  'max_bookings_per_week' => (int)($state['booking_policy']['max_bookings_per_week'] ?? 5),
              ];
          }
          
          return $state;
      })])
        ->columns(['md' => 2])
        ->columnSpanFull();

    }
} 