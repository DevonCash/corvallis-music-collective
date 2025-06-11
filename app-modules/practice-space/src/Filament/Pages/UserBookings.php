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
                    ->orderBy('start_time')
            )
            ->columns([
                Tables\Columns\TextColumn::make('room.name')
                    ->label('Room')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->label('Date')
                    ->dateTime('M j, Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->label('Time')
                    ->dateTime('g:i A')
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration')
                    ->label('Duration')
                    ->formatStateUsing(fn ($record) => $record->start_time->diffForHumans($record->end_time, ['parts' => 2]))
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_cost')
                    ->label('Cost')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('state')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => $state::getColor())
                    ->formatStateUsing(fn ($state) => $state::getLabel())
                    ->sortable(),
            ])
            ->actions(
                array_map(function ($action) {
                    if ($action->getName() === 'transition_to_cancelled') {
                        return $action->requiresConfirmation()
                            ->modalHeading('Cancel Booking')
                            ->modalDescription('Are you sure you want to cancel this booking? This action cannot be undone.')
                            ->modalSubmitActionLabel('Yes, cancel booking')
                            ->modalCancelActionLabel('No, keep booking');
                    }
                    return $action;
                }, BookingState::makeTransitionActions())
            )
            ->bulkActions([])
            ->emptyStateHeading('No bookings yet')
            ->emptyStateDescription('Once you book a practice room, your reservations will appear here.')
            ->emptyStateIcon('heroicon-o-calendar');
    }
}
