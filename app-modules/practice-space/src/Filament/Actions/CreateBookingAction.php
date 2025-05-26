<?php

namespace CorvMC\PracticeSpace\Filament\Actions;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;
use CorvMC\PracticeSpace\Models\Booking;
use CorvMC\PracticeSpace\Models\Room;
use Filament\Forms\Components\Wizard\Step;
use Illuminate\Support\HtmlString;
use Closure;
use Filament\Forms\Components\Hidden;
use CorvMC\PracticeSpace\Filament\Forms\Components\SelectRoom;
use Filament\Forms\Components\Component;

class CreateBookingAction
{
    /**
     * Render booking summary HTML using a booking instance
     *
     * @param Booking $booking
     * @return HtmlString
     */
    protected static function renderBookingSummary(Booking $booking): HtmlString
    {
        return new HtmlString(view('practice-space::filament.forms.booking-summary', [
            'booking' => $booking,
        ])->render());
    }

    public static function make(): Action
    {
        return Action::make('createBooking')
            ->label('Book a Room')
            ->color('primary')
            ->model(Booking::class)
            ->modalHeading('Schedule a Practice Room')
            ->modalDescription('Book a practice room for your rehearsal or practice session')
            ->steps(function (array $arguments) {
                return [
                    Step::make('Room & Time')
                        ->description('Select a room and booking time')
                        ->icon('heroicon-o-home')
                        ->columns(2)
                        ->schema([
                            Hidden::make('start_time'),
                            Hidden::make('end_time'),
                            SelectRoom::make('room_id')
                                ->preload()
                                ->required()
                                ->default($arguments['room_id'])
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                    // When room changes, clear time and duration to force reselection
                                    $set('booking_time', null);
                                    $set('duration_hours', null); // Clear duration
                                }),
                            Hidden::make('start_time'),
                            Forms\Components\DatePicker::make('booking_date')
                                ->required()
                                ->label('Date')
                                ->default(isset($arguments['booking_date']) ? $arguments['booking_date'] : null)
                                ->disabled(fn(Forms\Get $get) => $get('room_id') === null)
                                ->minDate(function (Forms\Get $get) {
                                    if (!$get('room_id')) return now();

                                    $roomId = $get('room_id');
                                    $room = Room::where('id', $roomId)->first();
                                    return $room->getMinimumBookingDate()->startOfDay();
                                })
                                ->maxDate(function (Forms\Get $get) {
                                    if (!$get('room_id')) return now()->addDays(90);

                                    $roomId = $get('room_id');
                                    $room = Room::where('id', $roomId)->first();
                                    return $room->getMaximumBookingDate();
                                })
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                    $set('booking_time', null);
                                    $set('duration_hours', null); // Clear duration
                                })
                                ->disabledDates(function (Forms\Get $get) {
                                    if (!$get('room_id')) return [];

                                    $roomId = $get('room_id');
                                    $room = Room::where('id', $roomId)->first();
                                    return $room->getDisabledBookingDates();
                                }),
                            Forms\Components\Select::make('booking_time')
                                ->required()
                                ->label('Start Time')
                                ->live()
                                ->default(isset($arguments['booking_time']) ? $arguments['booking_time'] : null)
                                ->disabled(fn(Forms\Get $get) => $get('booking_date') === null)
                                ->searchable()
                                ->options(function (Forms\Get $get) use ($arguments) {
                                    $bookingDate = $get('booking_date') ?? $arguments['booking_date'] ?? null;
                                    $roomId = $get('room_id') ?? $arguments['room_id'] ?? null;
                                    if (!$bookingDate || !$roomId) return [];

                                    $room = Room::where('id', $roomId)->first();
                                    if (!$room) return [];

                                    // Convert to Carbon if it's a string date
                                    if (is_string($bookingDate)) {
                                        $bookingDate = CarbonImmutable::parse($bookingDate . ' ' . $room->timezone);
                                    }

                                    $startTime = $bookingDate->startOfDay();
                                    $timeSlots = $room->getAvailableTimeSlots($startTime);
                                    return $timeSlots;
                                })->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                    if (!$get('room_id') || !$get('booking_date') || !$get('booking_time')) {
                                        $set('duration_hours', null);
                                        return;
                                    }

                                    $roomId = $get('room_id');
                                    $room = Room::where('id', $roomId)->first();
                                    if (!$room) {
                                        $set('duration_hours', null);
                                        return;
                                    }

                                    $bookingDate = $get('booking_date');
                                    $bookingTime = $get('booking_time');

                                    // Ensure we have proper date and time strings
                                    if (is_string($bookingDate) && is_string($bookingTime)) {
                                        $startTime = Carbon::createFromFormat('Y-m-d H:i', $bookingDate . ' ' . $bookingTime);
                                        $set('start_time', $startTime->format('Y-m-d H:i'));
                                    } else {
                                        $set('duration_hours', null);
                                        return;
                                    }

                                    $availableDurations = $room->getAvailableDurations($startTime);

                                    if (is_array($availableDurations) && !empty($availableDurations)) {
                                        // Get the first available duration option (first key in the array)
                                        $firstDuration = array_key_first($availableDurations);
                                        $set('duration_hours', $firstDuration);
                                    } else {
                                        $set('duration_hours', null);
                                    }
                                }),
                            Forms\Components\Select::make('duration_hours')
                                ->required()
                                ->label('Duration')
                                ->live()
                                ->disabled(fn(Forms\Get $get) => !$get('room_id') || !$get('booking_date') || !$get('booking_time'))
                                ->default(fn(Component $component) => array_key_first($component->getOptions()))
                                ->options(function (Forms\Get $get, Forms\Set $set) {
                                    if (!$get('room_id') || !$get('booking_date') || !$get('booking_time')) {
                                        return [];
                                    }

                                    $roomId = $get('room_id');
                                    $room = Room::where('id', $roomId)->first();
                                    if (!$room) return [];

                                    $bookingDate = $get('booking_date');
                                    $bookingTime = $get('booking_time');

                                    // Ensure we have proper date and time strings
                                    if (is_string($bookingDate) && is_string($bookingTime)) {
                                        $startTime = Carbon::createFromFormat('Y-m-d H:i', $bookingDate . ' ' . $bookingTime);
                                        $set('start_time', $startTime->format('Y-m-d H:i'));
                                    } else {
                                        return [];
                                    }
                                    $options = $room->getAvailableDurations($startTime);
                                    return $options;
                                })
                        ]),
                    Step::make('Review & Reserve')
                        ->description('Review booking details and reserve')
                        ->icon('heroicon-o-clipboard-document-check')
                        ->schema([
                            Forms\Components\Section::make('Booking Summary')
                                ->schema([
                                    Forms\Components\Placeholder::make('room_details')
                                        ->hiddenLabel()
                                        ->content(function (Forms\Get $get) {
                                            // If we don't have all the required data, show a message
                                            if (!$get('room_id') || !$get('booking_date') || !$get('booking_time') || !$get('duration_hours')) {
                                                return 'Please select a room and time on the previous step.';
                                            }

                                            try {
                                                $roomId = $get('room_id');
                                                $room = Room::where('id', $roomId)->first();
                                                if (!$room) {
                                                    return 'Invalid room selection.';
                                                }

                                                $bookingDate = $get('booking_date');
                                                $bookingTime = $get('booking_time');

                                                // Ensure we have proper date and time strings
                                                if (is_string($bookingDate) && is_string($bookingTime)) {
                                                    $start_time = Carbon::createFromFormat('Y-m-d H:i', $bookingDate . ' ' . $bookingTime);
                                                } else {
                                                    return 'Invalid date or time format.';
                                                }

                                                $end_time = $start_time->copy()->addHours(floatVal($get('duration_hours')));
                                                $booking = new Booking([
                                                    'room_id' => $roomId,
                                                    'start_time' => $start_time,
                                                    'end_time' => $end_time,
                                                    'user_id' => Auth::id(), // Default to current user
                                                    'notes' => $get('notes') ?? null,
                                                ]);

                                                return new HtmlString(view('practice-space::filament.forms.booking-summary', [
                                                    'booking' => $booking,
                                                ])->render());
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
                ];
            })
            ->action(function (array $data): void {
                // try {
                    // Create the booking directly
                    $room = Room::find($data['room_id'])->first();

                    $startDateTime = CarbonImmutable::createFromFormat('Y-m-d H:i', $data['booking_date'] . ' ' . $data['booking_time'], config('app.timezone'));
                    $endDateTime = $startDateTime->addHours(floatVal($data['duration_hours']));

                    $booking =  new Booking([
                        'room_id' => $data['room_id'],
                        'user_id' => Auth::id(),
                        'start_time' => $startDateTime,
                        'end_time' => $endDateTime,
                        'notes' => $data['notes'] ?? null,
                    ]);

                    $room->validateBooking($booking);

                    $booking->save();

                    Notification::make()
                        ->title($booking->room->name . ' booked for ' . $booking->start_time->format('Y-m-d g:i a'))
                        ->success()
                        ->send();
                // } catch (\Exception $e) {
                //     Notification::make()
                //         ->title('Room not available')
                //         ->body($e->getMessage())
                //         ->danger()
                //         ->send();
                // }
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
