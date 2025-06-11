<?php

namespace CorvMC\PracticeSpace\Filament\Resources;

use App\Models\User;
use CorvMC\PracticeSpace\Filament\Resources\BookingResource\Pages;
use CorvMC\PracticeSpace\Filament\Resources\BookingResource\RelationManagers;
use CorvMC\PracticeSpace\Models\Booking;
use CorvMC\PracticeSpace\Models\Room;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Grouping\Group;
use CorvMC\PracticeSpace\Models\States\BookingState;

use CorvMC\StateManagement\Filament\Table\Columns\StateColumn;
use Filament\Tables\Actions\Action;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationGroup = 'Practice Space';
    protected static ?string $navigationIcon = 'heroicon-o-calendar';


    
    public static function canAccess(): bool
    {
        /** @var User $user */
        $user = Auth::user();
        return $user->can('manage', Booking::class);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Booking Details')
                    ->schema([
                        Forms\Components\Select::make('room_id')
                            ->label('Room')
                            ->options(Room::query()->pluck('name', 'id'))
                            ->required()
                            ->searchable(),
                        Forms\Components\Select::make('user_id')
                            ->label('User')
                            ->options(User::query()->pluck('name', 'id'))
                            ->required()
                            ->searchable(),
                        Forms\Components\DateTimePicker::make('start_time')
                            ->required()
                            ->label('Start Time'),
                        Forms\Components\DateTimePicker::make('end_time')
                            ->required()
                            ->label('End Time')
                            ->after('start_time'),
                        Forms\Components\Select::make('status')
                            ->options([
                                'reserved' => 'Reserved',
                                'confirmed' => 'Confirmed',
                                'checked_in' => 'Checked In',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required()
                            ->default('reserved'),
                        Forms\Components\Textarea::make('notes')
                            ->maxLength(65535),
                    ]),
                Forms\Components\Section::make('Recurring Booking')
                    ->schema([
                        Forms\Components\Toggle::make('is_recurring')
                            ->label('Recurring Booking')
                            ->default(false),
                        Forms\Components\Select::make('recurring_pattern')
                            ->options([
                                'daily' => 'Daily',
                                'weekly' => 'Weekly',
                                'biweekly' => 'Bi-weekly',
                                'monthly' => 'Monthly',
                            ])
                            ->visible(fn (Forms\Get $get): bool => $get('is_recurring'))
                            ->required(fn (Forms\Get $get): bool => $get('is_recurring')),
                    ]),
                Forms\Components\Section::make('Check-in/Check-out')
                    ->schema([
                        Forms\Components\DateTimePicker::make('check_in_time')
                            ->label('Check-in Time'),
                        Forms\Components\DateTimePicker::make('check_out_time')
                            ->label('Check-out Time')
                            ->after('check_in_time'),
                    ]),
                ...(
                    app(\CorvMC\PracticeSpace\BookingSettings::class)->enable_payments
                        ? [
                            Forms\Components\Section::make('Payment')
                                ->schema([
                                    Forms\Components\TextInput::make('total_price')
                                        ->numeric()
                                        ->prefix('$')
                                        ->label('Total Price'),
                                    Forms\Components\Select::make('payment_status')
                                        ->options([
                                            'pending' => 'Pending',
                                            'paid' => 'Paid',
                                            'refunded' => 'Refunded',
                                            'failed' => 'Failed',
                                        ])
                                        ->default('pending'),
                                ]),
                        ]
                        : []
                ),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Booking ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('room.name')
                    ->label('Room')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->label('Start Time')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_time')
                    ->label('End Time')
                    ->dateTime()
                    ->sortable(),
                StateColumn::make('status'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(fn () => collect(BookingState::getStates())->mapWithKeys(fn ($state) => [$state::getName() => $state::getLabel()])->all()),
            ])
            ->actions([
                ...BookingState::makeTransitionTableActions('status'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->recordUrl(fn (Booking $record): string => static::getUrl('view', ['record' => $record]));
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\EquipmentRequestsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
            'create' => Pages\CreateBooking::route('/create'),
            'view' => Pages\ViewBooking::route('/{record}'),
            'edit' => Pages\EditBooking::route('/{record}/edit'),
        ];
    }
} 