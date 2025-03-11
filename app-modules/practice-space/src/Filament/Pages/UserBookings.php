<?php

namespace CorvMC\PracticeSpace\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use CorvMC\PracticeSpace\Models\Booking;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use CorvMC\PracticeSpace\Filament\Actions\CreateBookingAction;
use CorvMC\PracticeSpace\Models\States\BookingState;
use CorvMC\StateManagement\Filament\Actions\TransitionTableActions;
use Livewire\Attributes\On;
use CorvMC\PracticeSpace\Models\States\BookingState\{ConfirmedState, ScheduledState, CheckedInState};
use CorvMC\PracticeSpace\Models\Room;
use Carbon\Carbon;

class UserBookings extends Page implements HasTable
{
    use InteractsWithTable;
    
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Practice Space';
    // protected static ?string $navigationGroup = 'Practice Space';
    protected static ?string $title = 'Practice Space';
    protected static ?string $slug = 'practice-space/my-bookings';
    protected static ?int $navigationSort = 1;
    
    protected static string $view = 'practice-space::filament.pages.user-bookings';
    
    public function mount(): void
    {
        // No need to fill a form on mount anymore
    }
    
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Booking::query()
                    ->where('user_id', Auth::id())
                    ->where('start_time', '>=', now())
                    ->whereIn('state', [ConfirmedState::$name, ScheduledState::$name, CheckedInState::$name])
                    ->orderBy('start_time', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('room.name')
                    ->label('Room')
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->label('Date')
                    ->date()
                    ->description(fn (Booking $record) => $record->start_time->diffForHumans(now(), true))
                    ->sortable(),
                Tables\Columns\TextColumn::make('time')
                    ->label('Reservation Time')
                    ->getStateUsing(fn (Booking $record) => $record->start_time->format('g:i a') . ' - ' . $record->end_time->format('g:i a'))
                    ->description(fn (Booking $record) => $record->start_time->diffForHumans($record->end_time, true))
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_price')
                    ->label('Price')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('state')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn (Booking $record) => $record->state->getLabel())
                    ->color(fn (Booking $record) => $record->state->getColor()),
            ])
            ->defaultSort('start_time')
            ->actions(
                [...TransitionTableActions::make(BookingState::class)]
            )
            ->bulkActions([])
            ->emptyStateHeading('No bookings yet')
            ->emptyStateDescription('Once you book a practice room, your reservations will appear here.')
            ->emptyStateIcon('heroicon-o-calendar');
    }

    #[On('open-booking-form')]
    function openBookingForm(string $date, string $time, string $room_id)
    {
        // First mount the action
    $this->mountAction('create_booking');
    
    // Get the last index since we just mounted it
    $index = count($this->mountedActions) - 1;
    
    // Directly set the data in the mountedActionsData array
    $this->mountedActionsData[$index] = array_merge($this->mountedActionsData[$index], [
        
        'booking_date' => $date,
        'booking_time' => $time,
        'room_id' => $room_id
    ]);
    
    // Get the first available duration and set it
    $room = Room::find($room_id);
    if ($room) {
        $startTime = Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $time);
        $availableDurations = $room->getAvailableDurations($startTime, true);
        
        if (!empty($availableDurations)) {
            // Get the first available duration option (first key in the array)
            $firstDuration = array_key_first($availableDurations);
            $this->mountedActionsData[$index]['duration_hours'] = $firstDuration;
        }
    }
    
    // Force a form refresh
    $this->resetValidation();
    }
    
    protected function getHeaderActions(): array
    {
        return [
            CreateBookingAction::make(),
        ];
    }
} 