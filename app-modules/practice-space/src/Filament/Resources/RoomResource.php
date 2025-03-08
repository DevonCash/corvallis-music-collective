<?php

namespace CorvMC\PracticeSpace\Filament\Resources;

use CorvMC\PracticeSpace\Filament\Resources\RoomResource\Pages;
use CorvMC\PracticeSpace\Filament\Resources\RoomResource\RelationManagers;
use CorvMC\PracticeSpace\Models\Room;
use CorvMC\PracticeSpace\Models\RoomCategory;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class RoomResource extends Resource
{
    protected static ?string $model = Room::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    
    protected static ?string $navigationGroup = 'Practice Space';


    public static function canAccess(): bool
    {
        /** @var User $user */
        $user = Auth::user();
        return $user->can('manage', Room::class);
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Room Details')
                    ->schema([
                        Select::make('room_category_id')
                            ->label('Category')
                            ->relationship('category', 'name')
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Textarea::make('description')
                                    ->maxLength(65535),
                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),
                            ])
                            ->searchable(),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('description')
                            ->maxLength(65535),
                        TextInput::make('capacity')
                            ->required()
                            ->numeric()
                            ->minValue(1),
                        TextInput::make('hourly_rate')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->minValue(0),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ]),
                Forms\Components\Section::make('Room Media')
                    ->schema([
                        FileUpload::make('photos')
                            ->multiple()
                            ->directory('room-photos')
                            ->image()
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('16:9')
                            ->imageResizeTargetWidth('1920')
                            ->imageResizeTargetHeight('1080'),
                    ]),
                Forms\Components\Section::make('Room Specifications')
                    ->schema([
                        KeyValue::make('specifications')
                            ->keyLabel('Specification')
                            ->valueLabel('Detail')
                            ->addable()
                            ->reorderable(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable(),
                TextColumn::make('capacity')
                    ->sortable(),
                TextColumn::make('hourly_rate')
                    ->money('USD')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->relationship('category', 'name'),
                Filter::make('capacity')
                    ->form([
                        Forms\Components\TextInput::make('min_capacity')
                            ->numeric()
                            ->label('Minimum Capacity'),
                        Forms\Components\TextInput::make('max_capacity')
                            ->numeric()
                            ->label('Maximum Capacity'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_capacity'],
                                fn (Builder $query, $min): Builder => $query->where('capacity', '>=', $min),
                            )
                            ->when(
                                $data['max_capacity'],
                                fn (Builder $query, $max): Builder => $query->where('capacity', '<=', $max),
                            );
                    }),
                Filter::make('is_active')
                    ->toggle()
                    ->label('Show only active rooms')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            RelationManagers\EquipmentRelationManager::class,
            RelationManagers\MaintenanceSchedulesRelationManager::class,
            RelationManagers\BookingsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRooms::route('/'),
            'create' => Pages\CreateRoom::route('/create'),
            'view' => Pages\ViewRoom::route('/{record}'),
            'edit' => Pages\EditRoom::route('/{record}/edit'),
        ];
    }
} 