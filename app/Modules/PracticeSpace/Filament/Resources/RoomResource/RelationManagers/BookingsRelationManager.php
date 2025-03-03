<?php

namespace App\Modules\PracticeSpace\Filament\Resources\RoomResource\RelationManagers;

use AlpineIO\Filament\ModelStates\StateTableAction;
use AlpineIO\Filament\ModelStates\StateColumn;
use App\Modules\PracticeSpace\Models\States\BookingState;
use App\Modules\PracticeSpace\Models\States\BookingState\Confirmed;
use App\Modules\PracticeSpace\Models\States\BookingState\Cancelled;
use App\Modules\PracticeSpace\Models\States\BookingState\CheckedIn;
use App\Modules\PracticeSpace\Models\States\BookingState\Completed;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BookingsRelationManager extends RelationManager
{
    protected static string $relationship = 'bookings';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required()
                    ->label('User'),
                Forms\Components\DateTimePicker::make('start_time')
                    ->required()
                    ->label('Start Time'),
                Forms\Components\DateTimePicker::make('end_time')
                    ->required()
                    ->label('End Time')
                    ->after('start_time'),
                Forms\Components\Select::make('state')
                    ->options([
                        'scheduled' => 'Scheduled',
                        'confirmed' => 'Confirmed',
                        'checked_in' => 'Checked In',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->required()
                    ->label('Status'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->description(fn($record) => $record->user->email ?? '')
                    ->sortable()
                    ->searchable()
                    ->label('User'),
                TextColumn::make('start_time')
                    ->dateTime()
                    ->sortable()
                    ->label('Start Time'),
                TextColumn::make('end_time')
                    ->dateTime()
                    ->sortable()
                    ->label('End Time'),
                TextColumn::make('duration')
                    ->formatStateUsing(fn($record) => $record->duration . ' hour(s)')
                    ->label('Duration'),
                StateColumn::make('state')
                    ->badge()
                    ->label('Status'),
                TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->getStateUsing(fn($record) => $record->getAmountOwed() <= 0 ? 'Paid' : 'Unpaid')
                    ->color(fn($state) => $state === 'Paid' ? 'success' : 'gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('state')
                    ->options([
                        'scheduled' => 'Scheduled',
                        'confirmed' => 'Confirmed',
                        'checked_in' => 'Checked In',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->label('Status'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                StateTableAction::make('confirm')
                    ->transitionTo(BookingState\Confirmed::class)
                    ->visible(fn($record) => $record->state->canTransitionTo(Confirmed::class)),
                StateTableAction::make('cancel')
                    ->transitionTo(BookingState\Cancelled::class)
                    ->visible(fn($record) => $record->state->canTransitionTo(Cancelled::class)),
                StateTableAction::make('check_in')
                    ->transitionTo(BookingState\CheckedIn::class)
                    ->visible(fn($record) => $record->state->canTransitionTo(CheckedIn::class)),
                StateTableAction::make('complete')
                    ->transitionTo(BookingState\Completed::class)
                    ->visible(fn($record) => $record->state->canTransitionTo(Completed::class)),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
} 