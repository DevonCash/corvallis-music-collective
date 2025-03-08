<?php

namespace CorvMC\PracticeSpace\Filament\Pages;

use App\Models\User;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use CorvMC\PracticeSpace\Models\Booking;
use CorvMC\PracticeSpace\Models\Room;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Components\Wizard\Step;
use CorvMC\PracticeSpace\Filament\Actions\CreateBookingAction;
use CorvMC\PracticeSpace\Models\States\BookingState;
use Filament\Tables\Actions\Action as TableAction;
use CorvMC\StateManagement\Filament\Actions\TransitionTableActions;

class UserBookings extends Page implements HasTable
{
    use InteractsWithTable;
    
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationLabel = 'My Practice Room Bookings';
    // protected static ?string $navigationGroup = 'Practice Space';
    protected static ?string $title = 'My Practice Room Bookings';
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
            )
            ->columns([
                Tables\Columns\TextColumn::make('room.name')
                    ->label('Room')
                    ->searchable()
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
            ->filters([
                Tables\Filters\SelectFilter::make('state')
                    ->label('Status')
                    ->options([
                        'reserved' => 'Reserved',
                        'confirmed' => 'Confirmed',
                        'checked_in' => 'Checked In',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\Filter::make('upcoming')
                    ->label('Upcoming Bookings')
                    ->query(fn (Builder $query): Builder => $query->where('start_time', '>=', now())),
                Tables\Filters\Filter::make('past')
                    ->label('Past Bookings')
                    ->query(fn (Builder $query): Builder => $query->where('end_time', '<', now())),
            ])
            ->actions(
                [...TransitionTableActions::make(BookingState::class)]
            )
            ->bulkActions([])
            ->emptyStateHeading('No bookings yet')
            ->emptyStateDescription('Once you book a practice room, your reservations will appear here.')
            ->emptyStateIcon('heroicon-o-calendar');
    }
    
    protected function getHeaderActions(): array
    {
        return [
            CreateBookingAction::make(),
        ];
    }
} 