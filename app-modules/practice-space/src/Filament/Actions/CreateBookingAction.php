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
        
        $html = view('practice-space::filament.forms.booking-summary', [
            'room' => $room,
            'booking_date' => $booking->start_time->format('Y-m-d'),
            'booking_time' => $booking->start_time->format('H:i'),
            'end_time' => $booking->end_time->format('H:i'),
            'duration_hours' => $booking->end_time->diffInHours($booking->start_time),
            'hourly_rate' => $room->hourly_rate,
            'total_price' => $booking->total_price,
        ])->render();
        
        return new HtmlString($html);
    }
    
    public static function make(): Action
    {
        $bookingService = new BookingService();
        
        return Action::make('create_booking')
            ->label('Book a Room')
            ->color('primary')
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
                                    ->options(function () use ($bookingService) {
                                        // Get all active rooms
                                        return $bookingService->getRoomOptions();
                                    })
                                    ->required()
                                    ->searchable()
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
                                    ->minDate(now())
                                    ->live()
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
                                    ->options(function (Forms\Get $get) use ($bookingService) {
                                        // If no room or date is selected, show all half-hour options
                                        if (!$get('room_id') || !$get('booking_date')) {
                                            return self::generateTimeOptions();
                                        }
                                        
                                        // Get available time slots for this room and date
                                        // Use a default duration of 0.5 hour to show all possible start times
                                        return $bookingService->getAvailableTimeSlots(
                                            $get('room_id'),
                                            $get('booking_date'),
                                            0.5 // Use 0.5 hour to show all possible start times
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
                                            // Get available durations for this room, date, and time
                                            $availableDurations = $bookingService->getAvailableDurations(
                                                $get('room_id'),
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
                                                
                                                // Calculate booking times
                                                $times = $bookingService->calculateBookingTimes($formData);
                                                $startDateTime = $times['start_datetime'];
                                                $endDateTime = $times['end_datetime'];
                                                
                                                // Check if room is available
                                                if (!$bookingService->isRoomAvailable($formData['room_id'], $startDateTime, $endDateTime)) {
                                                    $fail('The selected room is not available for the chosen time slot. Please select a different time or room.');
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