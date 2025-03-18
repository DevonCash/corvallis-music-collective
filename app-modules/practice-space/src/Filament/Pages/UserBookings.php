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
use CorvMC\PracticeSpace\Models\States\BookingState;
use CorvMC\StateManagement\Filament\Actions\TransitionTableActions;
use CorvMC\PracticeSpace\Models\States\BookingState\{ConfirmedState, ScheduledState, CheckedInState};

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
} 