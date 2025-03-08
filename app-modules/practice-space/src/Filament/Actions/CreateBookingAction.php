<?php

namespace CorvMC\PracticeSpace\Filament\Actions;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;
use CorvMC\PracticeSpace\Models\Booking;
use CorvMC\PracticeSpace\Models\Room;
use Filament\Forms\Components\Wizard\Step;
use CorvMC\PracticeSpace\Services\BookingService;
use Illuminate\Support\HtmlString;
use Closure;

class CreateBookingAction
{
    /**
     * Render booking summary HTML using a booking instance
     *
     * @param Booking $booking
     * @param BookingService $bookingService
     * @return HtmlString
     */
    protected static function renderBookingSummary(Booking $booking, BookingService $bookingService): HtmlString
    {
        $room = $bookingService->getRoomById($booking->room_id);
        $policy = $room->getBookingPolicy();
        
        $html = view('practice-space::filament.forms.booking-summary', [
            'room' => $room,
            'booking_date' => $booking->start_time->format('Y-m-d'),
            'booking_time' => $booking->start_time->format('H:i'),
            'end_time' => $booking->end_time->format('H:i'),
            'duration_hours' => $booking->end_time->diffInHours($booking->start_time),
            'hourly_rate' => $room->hourly_rate,
            'total_price' => $booking->total_price,
            'room_policy' => $policy,
            'room_description' => $room->description,
            'room_capacity' => $room->capacity,
            'room_specifications' => $room->specifications,
        ])->render();
        
        return new HtmlString($html);
    }
    
    public static function make(): Action
    {
        $bookingService = new BookingService();
        
        return Action::make('create_booking')
            ->label('Book a Room')
            ->color('primary')
            ->model(Booking::class)
            ->modalHeading('Schedule a Practice Room')
            ->modalDescription('Book a practice room for your rehearsal or practice session')
            ->steps([
                Step::make('Room & Time')
                    ->description('Select a room and booking time')
                    ->icon('heroicon-o-home')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('room_id')
                                    ->label('Room')
                                    ->visible(fn() => Room::count() > 1)
                                    ->default(Room::first()->id)
                                    ->relationship('room', 'name', function ($query) {
                                        // Only show active rooms
                                        return $query->where('is_active', true);
                                    })
                                    ->getOptionLabelFromRecordUsing(function (Room $record) {
                                        // Format the room option to show more details
                                        $policy = $record->getBookingPolicy();
                                        
                                        // Format price - show without cents if it's a whole dollar amount
                                        $hourlyRate = $record->hourly_rate;
                                        $formattedPrice = floor($hourlyRate) == $hourlyRate 
                                            ? '$' . number_format($hourlyRate, 0) 
                                            : '$' . number_format($hourlyRate, 2);
                                        
                                        // Format duration - show "30 mins" instead of "0.5hr"
                                        $minDuration = $policy->minBookingDurationHours;
                                        $formattedDuration = $minDuration == 0.5 
                                            ? '30 mins' 
                                            : $minDuration . 'hr';
                                        
                                        // Create a two-row display with HTML formatting and icons
                                        // Matching the style in the screenshot more closely
                                        return "
                                            <div class='flex flex-col py-1'>
                                                <span class='font-medium text-gray-900'>{$record->name}</span>
                                                <div class='text-sm text-gray-500 flex items-center gap-2 mt-1'>
                                                    <span>{$formattedPrice}/hr</span>
                                                    <span class='flex items-center'>
                                                        <svg class='w-4 h-4 mr-1 text-gray-400' xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'>
                                                            <circle cx='12' cy='12' r='10'></circle>
                                                            <polyline points='12 6 12 12 16 14'></polyline>
                                                        </svg>
                                                        {$formattedDuration}
                                                    </span>
                                                    <span class='flex items-center'>
                                                        <svg class='w-4 h-4 mr-1 text-gray-400' xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'>
                                                            <path d='M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2'></path>
                                                            <circle cx='12' cy='7' r='4'></circle>
                                                        </svg>
                                                        {$record->capacity}
                                                    </span>
                                                </div>
                                                " . ($record->description ? "<div class='text-xs text-gray-400 mt-1 truncate max-w-md'>{$record->description}</div>" : "") . "
                                            </div>
                                        ";
                                    })
                                    ->allowHtml()
                                    ->searchable(['name', 'description'])
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        // When room changes, clear time and duration to force reselection
                                        $set('booking_time', null);
                                        $set('duration_hours', null); // Clear duration
                                        $set('end_time', null);
                                    }),
                                Forms\Components\DatePicker::make('booking_date')
                                    ->required()
                                    ->label('Date')
                                    ->timezone('America/Los_Angeles')
                                    ->minDate(function (Forms\Get $get) use ($bookingService) {
                                        // If no room is selected, use today as the minimum date
                                        if (!$get('room_id')) {
                                            return now();
                                        }
                                        
                                        // Get the room from the selected record
                                        $roomId = $get('room_id');
                                        $room = Room::find($roomId);
                                        if (!$room) {
                                            return now();
                                        }
                                        
                                        // Get the minimum advance booking hours from the room's policy
                                        $policy = $room->getBookingPolicy();
                                        
                                        // If the minimum lead time is less than 24 hours, we need to handle it specially
                                        if ($policy->minAdvanceBookingHours < 24) {
                                            // For same-day bookings, we'll still allow today as the minimum date
                                            // The time validation will handle restricting the available time slots
                                            return now();
                                        } else {
                                            // For lead times of 24+ hours, add the appropriate number of days
                                            $daysToAdd = ceil($policy->minAdvanceBookingHours / 24);
                                            return now()->addDays($daysToAdd);
                                        }
                                    })
                                    ->maxDate(function (Forms\Get $get) use ($bookingService) {
                                        // If no room is selected, use a default max date (e.g., 90 days from now)
                                        if (!$get('room_id')) {
                                            return now()->addDays(90);
                                        }
                                        
                                        // Get the room from the selected record
                                        $roomId = $get('room_id');
                                        $room = Room::find($roomId);
                                        if (!$room) {
                                            return now()->addDays(90);
                                        }
                                        
                                        // Get the max advance booking days from the room's policy
                                        $policy = $room->getBookingPolicy();
                                        return now()->addDays($policy->maxAdvanceBookingDays);
                                    })
                                    ->live()
                                    ->disabled(fn (Forms\Get $get)=> $get('room_id') === null)
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        // When date changes, clear time and duration to force reselection
                                        $set('booking_time', null);
                                        $set('duration_hours', null); // Clear duration
                                        $set('end_time', null);
                                    })
                                    ->disabledDates(function (Forms\Get $get) use ($bookingService) {
                                        // If no room is selected, don't disable any dates
                                        if (!$get('room_id')) {
                                            return [];
                                        }
                                        
                                        // Get dates when the room is fully booked
                                        return $bookingService->getFullyBookedDates($get('room_id'));
                                    }),
                                Forms\Components\Select::make('booking_time')
                                    ->required()
                                    ->label('Start Time')
                                    ->live()
                                    ->disabled(fn (Forms\Get $get)=> $get('room_id') === null)

                                    ->options(function (Forms\Get $get) use ($bookingService) {
                                        // If no room or date is selected, show all half-hour options
                                        if (!$get('room_id') || !$get('booking_date')) {
                                            return self::generateTimeOptions();
                                        }
                                        
                                        // Get the room from the selected record
                                        $roomId = $get('room_id');
                                        $room = Room::find($roomId);
                                        if (!$room) {
                                            return self::generateTimeOptions();
                                        }
                                        
                                        // Get the minimum booking duration from the room's policy
                                        $policy = $room->getBookingPolicy();
                                        $minDuration = $policy->minBookingDurationHours;
                                        
                                        // Use the room's booking policy for time slots
                                        // Get available time slots for this room and date
                                        return $bookingService->getAvailableTimeSlots(
                                            $roomId,
                                            $get('booking_date'),
                                            $minDuration // Use the minimum booking duration from the policy
                                        );
                                    })
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        // When time changes, don't reset duration - just clear it if not set
                                        if (!$get('duration_hours')) {
                                            $set('duration_hours', null);
                                        }
                                        
                                        // Update end time if duration is set
                                        if ($get('booking_date') && $state && $get('duration_hours')) {
                                            $startDateTime = Carbon::parse($get('booking_date') . ' ' . $state);
                                            $endDateTime = $startDateTime->copy()->addMinutes((float)$get('duration_hours') * 60);
                                            $set('end_time', $endDateTime->format('H:i'));
                                        } else {
                                            $set('end_time', null);
                                        }
                                    }),
                                Forms\Components\Select::make('duration_hours')
                                    ->required()
                                    ->label('Duration')
                                    ->live()
                                    ->disabled(fn (Forms\Get $get)=> $get('room_id') === null)

                                    ->options(function (Forms\Get $get) use ($bookingService) {
                                        // Default duration options with half-hour increments
                                        $defaultOptions = [
                                            0.5 => '30 minutes',
                                            1 => '1 hour',
                                            1.5 => '1.5 hours',
                                            2 => '2 hours',
                                            2.5 => '2.5 hours',
                                            3 => '3 hours',
                                            3.5 => '3.5 hours',
                                            4 => '4 hours',
                                            4.5 => '4.5 hours',
                                            5 => '5 hours',
                                            5.5 => '5.5 hours',
                                            6 => '6 hours',
                                            6.5 => '6.5 hours',
                                            7 => '7 hours',
                                            7.5 => '7.5 hours',
                                            8 => '8 hours',
                                        ];
                                        
                                        // If no room, date, or time is selected, show all duration options
                                        if (!$get('room_id') || !$get('booking_date') || !$get('booking_time')) {
                                            return $defaultOptions;
                                        }
                                        
                                        try {
                                            // Get the room from the selected record
                                            $roomId = $get('room_id');
                                            $room = Room::find($roomId);
                                            if (!$room) {
                                                return $defaultOptions;
                                            }
                                            
                                            $policy = $room->getBookingPolicy();
                                            
                                            // Get available durations for this room, date, and time
                                            // This will respect the room's booking policy
                                            $availableDurations = $bookingService->getAvailableDurations(
                                                $roomId,
                                                $get('booking_date'),
                                                $get('booking_time'),
                                                true // Include half-hour increments
                                            );
                                            
                                            // If no durations are available, return default options
                                            // This prevents the dropdown from being empty
                                            if (empty($availableDurations)) {
                                                return $defaultOptions;
                                            }
                                            
                                            return $availableDurations;
                                        } catch (\Exception $e) {
                                            // If there's an error, return default options
                                            return $defaultOptions;
                                        }
                                    })
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        // Calculate end time
                                        if ($get('booking_date') && $get('booking_time') && $state) {
                                            $startDateTime = Carbon::parse($get('booking_date') . ' ' . $get('booking_time'));
                                            $endDateTime = $startDateTime->copy()->addMinutes((float)$state * 60);
                                            $set('end_time', $endDateTime->format('H:i'));
                                        } else {
                                            $set('end_time', null);
                                        }
                                    }),
                                Forms\Components\Hidden::make('end_time'),
                                // Add a hidden component to validate room availability
                                Forms\Components\Hidden::make('availability_check')
                                    ->rules([
                                        function (Forms\Get $get) use ($bookingService): Closure {
                                            return function (string $attribute, $value, Closure $fail) use ($get, $bookingService) {
                                                // Check if all required fields are filled
                                                if (!$get('room_id') || !$get('booking_date') || !$get('booking_time') || !$get('duration_hours')) {
                                                    return;
                                                }
                                                
                                                // Collect form data
                                                $formData = [
                                                    'room_id' => $get('room_id'),
                                                    'booking_date' => $get('booking_date'),
                                                    'booking_time' => $get('booking_time'),
                                                    'end_time' => $get('end_time'),
                                                    'duration_hours' => $get('duration_hours'),
                                                ];
                                                
                                                // Use the BookingService to validate the booking data
                                                $validationResult = $bookingService->validateBookingData($formData);
                                                
                                                if (!$validationResult['is_valid']) {
                                                    $fail($validationResult['error_message']);
                                                }
                                            };
                                        }
                                    ])
                                    ->dehydrated(false),
                            ]),
                    ]),
                Step::make('Review & Confirm')
                    ->description('Review booking details and confirm')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->schema([
                        Forms\Components\Section::make('Booking Summary')
                            ->schema([
                              Forms\Components\Placeholder::make('room_details')
                                ->label('Room Details')
                                ->content(function(Forms\Get $get) use ($bookingService) {
                                    // If we don't have all the required data, show a message
                                    if (!$get('room_id') || !$get('booking_date') || !$get('booking_time') || !$get('duration_hours')) {
                                        return 'Please select a room and time on the previous step.';
                                    }
                                    
                                    try {
                                        // Collect form data
                                        $formData = [
                                            'room_id' => $get('room_id'),
                                            'booking_date' => $get('booking_date'),
                                            'booking_time' => $get('booking_time'),
                                            'end_time' => $get('end_time'),
                                            'duration_hours' => $get('duration_hours'),
                                        ];
                                        
                                        // Create a booking instance without saving
                                        $booking = $bookingService->createBookingInstance($formData);
                                        
                                        // Render the summary
                                        return self::renderBookingSummary($booking, $bookingService);
                                    } catch (\Exception $e) {
                                        // If there's an error, show a message
                                        return new HtmlString('<div class="text-danger-500">' . $e->getMessage() . '</div>');
                                    }
                                }),
                                Forms\Components\Textarea::make('notes')
                                    ->label('Notes')
                                    ->placeholder('Any special requirements or notes for your booking')
                                    ->maxLength(1000),
                            ]),
                    ]),
            ])
            ->action(function (array $data) use ($bookingService): void {
                try {
                    // Create the booking using the service
                    $booking = $bookingService->createBooking($data);
                    
                    Notification::make()
                        ->title('Booking created successfully')
                        ->success()
                        ->send();
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Room not available')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
    
    /**
     * Generate time options for the time picker (half-hour intervals)
     * 
     * @return array
     */
    protected static function generateTimeOptions(): array
    {
        $options = [];
        $start = Carbon::createFromTime(8, 0); // Start at 8:00 AM
        $end = Carbon::createFromTime(22, 0);  // End at 10:00 PM
        
        while ($start <= $end) {
            $timeString = $start->format('H:i');
            $displayTime = $start->format('g:i A');
            $options[$timeString] = $displayTime;
            
            $start->addMinutes(30);
        }
        
        return $options;
    }
} 