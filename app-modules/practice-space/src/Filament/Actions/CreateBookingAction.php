<?php

namespace CorvMC\PracticeSpace\Filament\Actions;

use Carbon\Carbon;
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
use Illuminate\Support\Facades\Blade;
use Livewire\Attributes\On;

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
                    ->columns(2)
                    ->schema([
                        Forms\Components\Hidden::make('timezone')
                            ->extraAttributes([
                                'x-data' => '{}',
                                'x-init' => '$nextTick(() => $el.dispatchEvent(new Event("input", {bubbles: true})))',
                                ':value' => 'Intl.DateTimeFormat().resolvedOptions().timeZone',
                            ]),
                        Forms\Components\Select::make('room_id')
                            ->label('Room')
                            ->visible(fn() => Room::count() > 1)
                            ->default(function () {
                                return Room::first()->id;
                            })
                            ->relationship('room', 'name')
                            ->getOptionLabelFromRecordUsing(function (Room $record) {
                                // Format the room option to show more details

                                // Format price - show without cents if it's a whole dollar amount
                                $hourlyRate = $record->hourly_rate;
                                $formattedPrice = floor($hourlyRate) == $hourlyRate
                                    ? '$' . number_format($hourlyRate, 0)
                                    : '$' . number_format($hourlyRate, 2);

                                // Create a two-row display with HTML formatting and icons
                                // Matching the style in the screenshot more closely
                                return Blade::render("
                                            <div class='flex flex-col py-1'>
                                            <div class='text-sm text-gray-500 flex items-center gap-2 mt-1'>
                                                <span class='font-medium text-gray-900'>{$record->name}</span>
                                                    <span>{$formattedPrice}/hr</span>
                                                    <span class='flex items-center'>
                                                        <x-filament::icon icon='heroicon-o-users' class='w-4 h-4 text-gray-400 mr-1' />
                                                        {$record->capacity}
                                                    </span>
                                                </div>
                                                " . ($record->description ? "<div class='text-xs text-gray-400 mt-1 truncate max-w-md'>{$record->description}</div>" : "") . "
                                            </div>
                                        ");
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
                            }),
                        Hidden::make('start_time'),
                        Forms\Components\DatePicker::make('booking_date')
                            ->required()
                            ->label('Date')
                            ->disabled(fn(Forms\Get $get) => $get('room_id') === null)
                            ->minDate(function (Forms\Get $get) {
                                if (!$get('room_id')) return now();

                                $room = Room::find($get('room_id'));
                                return $room->getMinimumBookingDate();
                            })
                            ->maxDate(function (Forms\Get $get) {
                                if (!$get('room_id')) return now()->addDays(90);

                                $room = Room::find($get('room_id'));
                                return $room->getMaximumBookingDate();
                            })
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                $set('booking_time', null);
                                $set('duration_hours', null); // Clear duration
                            })
                            ->disabledDates(function (Forms\Get $get) {
                                if (!$get('room_id')) return [];

                                $room = Room::find($get('room_id'));
                                return $room->getDisabledBookingDates();
                            }),
                        Forms\Components\Select::make('booking_time')
                            ->required()
                            ->label('Start Time')
                            ->live()
                            ->disabled(fn(Forms\Get $get) => $get('booking_date') === null)
                            ->searchable()
                            ->options(function (Forms\Get $get) {
                                if (!$get('room_id') || !$get('booking_date')) return [];

                                $room = Room::find($get('room_id'));
                                $startTime = Carbon::createFromFormat('Y-m-d', $get('booking_date'), $get('timezone'))->startOfDay();
                                return $room->getAvailableTimeSlots($startTime);
                            })->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                // Instead of clearing duration, set it to the first available option
                                if (!$get('room_id') || !$get('booking_date') || !$get('booking_time')) {
                                    $set('duration_hours', null);
                                    return;
                                }
                                
                                $room = Room::find($get('room_id'));
                                $startTime = Carbon::createFromFormat('Y-m-d H:i', $get('booking_date') . ' ' . $get('booking_time'), $get('timezone'));
                                $availableDurations = $room->getAvailableDurations($startTime, true);
                                
                                if (!empty($availableDurations)) {
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
                            ->options(function (Forms\Get $get) {
                                if (!$get('room_id') || !$get('booking_date') || !$get('booking_time')) {
                                    return [];
                                }
                                $room = Room::find($get('room_id'));
                                $startTime = Carbon::createFromFormat('Y-m-d H:i', $get('booking_date') . ' ' . $get('booking_time'), $get('timezone'));
                                return $room->getAvailableDurations($startTime, true);
                            })
                    ]),
                Step::make('Review & Confirm')
                    ->description('Review booking details and confirm')
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
                                            // Collect form data
                                            $start_time = Carbon::createFromFormat('Y-m-d H:i', $get('booking_date') . ' ' . $get('booking_time'), $get('timezone'));
                                            $end_time = $start_time->copy()->addHours(floatVal($get('duration_hours')));
                                            $booking = new Booking([
                                                'room_id' => $get('room_id'),
                                                'start_time' => $start_time,
                                                'end_time' => $end_time,
                                                'user_id' => $get('user_id'),
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
            ])
            ->action(function (array $data): void {
                try {
                    // Create the booking directly
                    $startDateTime = Carbon::createFromFormat('Y-m-d H:i', $data['booking_date'] . ' ' . $data['booking_time'], $data['timezone']);
                    $endDateTime = $startDateTime->copy()->addHours(floatVal($data['duration_hours']));
                    $booking =  new Booking([
                        'room_id' => $data['room_id'],
                        'user_id' => Auth::id(),
                        'start_time' => $startDateTime,
                        'end_time' => $endDateTime,
                        'notes' => $data['notes'] ?? null,
                    ]);

                    $booking->save();

                    Notification::make()
                        ->title($booking->room->name . ' booked for ' . $booking->start_time->format('Y-m-d g:i a'))
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
