<?php

namespace App\Modules\PracticeSpace\Filament\Resources;

use AlpineIO\Filament\ModelStates\StateTableAction;
use AlpineIO\Filament\ModelStates\StateColumn;
use App\Modules\PracticeSpace\Filament\Resources\BookingResource\Pages;
use App\Modules\PracticeSpace\Filament\Resources\BookingResource\RelationManagers;
use App\Modules\PracticeSpace\Services\BookingService;
use App\Modules\PracticeSpace\Models\Booking;
use App\Modules\PracticeSpace\Models\Room;
use App\Modules\PracticeSpace\Models\States\BookingState;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationGroup = 'Practice Space';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->default(fn() => Auth::id())
                    ->required()
                    ->live()
                    ->label('User'),
                Select::make('room_id')
                    ->relationship('room', 'name')
                    ->live()
                    ->required()
                    ->label('Room')
                    ->afterStateUpdated(function($state, $set) {
                        // Reset dependent fields when room changes
                        $set('date', null);
                        $set('duration', null);
                        $set('start_time', null);
                    }),
                DatePicker::make('date')
                    ->required()
                    ->live()
                    ->disabled(fn(Get $get) => !$get('room_id'))
                    ->disabledDates(function(Get $get) {
                        $roomId = $get('room_id');
                        $duration = $get('duration');
                        $startTime = $get('start_time');
                        
                        if ($startTime && $duration) {
                            // If start time and duration are set, we need dates where that combination is available
                            return BookingService::getUnavailableDatesForTimeAndDuration($roomId, $startTime, $duration);
                        }
                        
                        if ($duration) {
                            // If only duration is set, get dates that can accommodate this duration
                            return BookingService::getUnavailableDatesForDuration($roomId, $duration);
                        }
                        
                        // Default behavior - get all unavailable dates
                        return BookingService::getUnavailableDates($roomId);
                    })
                    ->afterStateUpdated(function($state, $set, $get) {
                        // If the date changed but we have start_time and duration, we don't reset them
                        // as they might still be valid for the new date
                        $startTime = $get('start_time');
                        $duration = $get('duration');
                        $roomId = $get('room_id');
                        
                        if ($startTime && $duration && $state) {
                            // Validate if the current combination is still valid
                            $availableTimes = BookingService::getAvailableTimes($roomId, $state, $duration);
                            if (!isset($availableTimes[$startTime])) {
                                $set('start_time', null);
                            }
                        }
                    })
                    ->label('Date'),
                Select::make('duration')
                    ->live()
                    ->disabled(fn(Get $get) => !$get('room_id'))
                    ->options(function(Get $get) {
                        $roomId = $get('room_id');
                        $date = $get('date');
                        $startTime = $get('start_time');
                        
                        if (!$roomId) {
                            return [];
                        }
                        
                        if ($date && $startTime) {
                            // If date and start time are set, get durations available from that time
                            return BookingService::getAvailableDurationsForDateTime($roomId, $date, $startTime);
                        }
                        
                        if ($date) {
                            // If only date is set, get all durations available on that date
                            return BookingService::getAvailableDurations($date, $roomId);
                        }
                        
                        if ($startTime) {
                            // If only start time is set (time of day), get durations available for that time across valid dates
                            return BookingService::getAvailableDurationsForTime($roomId, $startTime);
                        }
                        
                        // If neither date nor start time are set, return standard duration options
                        return [
                            1 => '1 hour',
                            2 => '2 hours',
                            3 => '3 hours',
                            4 => '4 hours',
                        ];
                    })
                    ->afterStateUpdated(function($state, $set, $get) {
                        // If duration changed, check if the start time is still valid
                        $date = $get('date');
                        $startTime = $get('start_time');
                        $roomId = $get('room_id');
                        
                        if ($date && $startTime && $state) {
                            // Validate if the current combination is still valid
                            $availableTimes = BookingService::getAvailableTimes($roomId, $date, $state);
                            if (!isset($availableTimes[$startTime])) {
                                $set('start_time', null);
                            }
                        }
                    }),
                Select::make('start_time')
                    ->live()
                    ->disabled(fn(Get $get) => !$get('room_id'))
                    ->options(function(Get $get) {
                        $roomId = $get('room_id');
                        $date = $get('date');
                        $duration = $get('duration');
                        
                        if (!$roomId) {
                            return [];
                        }
                        
                        if ($date && $duration) {
                            // If date and duration are set, get available times
                            return BookingService::getAvailableTimes($roomId, $date, $duration);
                        }
                        
                        if ($date) {
                            // If only date is set, get all possible start times
                            return BookingService::getAllAvailableTimesForDate($roomId, $date);
                        }
                        
                        if ($duration) {
                            // If only duration is set, get times that work for that duration across dates
                            return BookingService::getAvailableTimesForDuration($roomId, $duration);
                        }
                        
                        // If neither date nor duration are set, return standard time options
                        return BookingService::getStandardTimeOptions($roomId);
                    })
                    ->afterStateUpdated(function($state, $set, $get) {
                        // When start time changes, check if date and duration are still compatible
                        $date = $get('date');
                        $duration = $get('duration');
                        $roomId = $get('room_id');
                        
                        // If we have all three, we need to validate the combination
                        if ($date && $duration && $state) {
                            $availableTimes = BookingService::getAvailableTimes($roomId, $date, $duration);
                            if (!isset($availableTimes[$state])) {
                                // This combination is no longer valid, reset values
                                if (!BookingService::isTimeAvailableForAnyDuration($roomId, $date, $state)) {
                                    // The time is not valid for any duration on this date
                                    $set('start_time', null);
                                } else {
                                    // The time is valid for some duration, just not the one selected
                                    $set('duration', null);
                                }
                            }
                        }
                    }),
            ])
            ->statePath('data')
            ->onSave(function (Form $form): void {
                $data = $form->getState();

                // Only proceed if we have all the required fields
                if (!empty($data['date']) && !empty($data['start_time']) && !empty($data['duration'])) {
                    // Create start_time datetime by combining date and time
                    $dateTimeStr = $data['date'] . ' ' . $data['start_time'];
                    $startDateTime = Carbon::createFromFormat('Y-m-d H:i', $dateTimeStr);
                    $endDateTime = $startDateTime->copy()->addHours($data['duration']);
                    
                    // Update the data to be saved
                    $form->fill([
                        'start_time' => $startDateTime,
                        'end_time' => $endDateTime,
                    ]);
                }
            });
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->description(fn($record) => $record->user->email)
                    ->label('User'),
                TextColumn::make('room.name')
                    ->label('Room'),
                StateColumn::make('state')
                    ->badge()
                    ->label('Status'),
                TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->getStateUsing(function($record) {
                        // Check if any payment is in Refunded state
                        $hasRefundedPayment = $record->payments()
                            ->where('state', 'App\\Modules\\Payments\\Models\\States\\PaymentState\\Refunded')
                            ->exists();
                            
                        if ($hasRefundedPayment) {
                            return 'Refunded';
                        }
                        
                        // Otherwise use the regular payment check
                        return $record->getAmountOwed() <= 0 ? 'Paid' : 'Unpaid';
                    })
                    ->color(function($state) {
                        return match ($state) {
                            'Paid' => 'success',
                            'Refunded' => 'warning',
                            default => 'gray',
                        };
                    }),
                TextColumn::make('start_time')
                    ->date()
                    ->description(fn($record) =>
                    "{$record->start_time->format('g:i a')} - {$record->end_time->format('g:i a')}")
                    ->label('Date'),
            ])
            ->filters([
                //
            ])
            ->actions([
                StateTableAction::make('confirm')
                    ->transitionTo(BookingState\Confirmed::class),
                StateTableAction::make('cancel')
                    ->transitionTo(BookingState\Cancelled::class),
                StateTableAction::make('check_in')
                    ->transitionTo(BookingState\CheckedIn::class),
                StateTableAction::make('complete')
                    ->transitionTo(BookingState\Completed::class),

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
            'view' => Pages\ViewBooking::route('/{record}'),
        ];
    }
}
