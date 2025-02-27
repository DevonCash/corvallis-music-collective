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

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->default(fn() => Auth::id())
                    ->required()
                    ->label('User'),
                Select::make('room_id')
                    ->relationship('room', 'name')
                    ->required()
                    ->label('Room'),
                DatePicker::make('date')
                    ->required()
                    ->disabled(fn(Get $get) => ! $get('room_id'))
                    ->disabledDates(fn(Get $get) => BookingService::getUnavailableDates($get('room_id')))
                    ->label('Date'),
                Select::make('duration')
                    ->disabled(fn(Get $get) => ! $get('date'))
                    ->options(fn(Get $get) => BookingService::getAvailableDurations($get('date'), $get('room_id'))),
                Select::make('start_time')
                    ->disabled(fn(Get $get) => ! $get('duration'))
                    ->options(fn($get) => BookingService::getAvailableTimes($get('room_id'), $get('date'), $get('duration')))
            ]);
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
                    ->getStateUsing(fn($record) => $record->getAmountOwed() <= 0 ? 'Paid' : 'Unpaid')
                    ->color(fn($state) => $state === 'Paid' ? 'success' : 'gray'),
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
