<?php

namespace CorvMC\PracticeSpace\Filament\Resources;

use CorvMC\PracticeSpace\Filament\Forms\Components\BookingPolicyForm;
use CorvMC\PracticeSpace\Filament\Resources\RoomCategoryResource\Pages;
use CorvMC\PracticeSpace\Filament\Resources\RoomCategoryResource\RelationManagers;
use CorvMC\PracticeSpace\Models\RoomCategory;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
class RoomCategoryResource extends Resource
{
    protected static ?string $model = RoomCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Practice Space';


    public static function canAccess(): bool
    {
        /** @var User $user */
        $user = Auth::user();
        return $user->can('manage', RoomCategory::class);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Room Category')
                    ->tabs([
                        Tab::make('Details')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Forms\Components\Section::make('Category Information')
                                    ->description('Enter the basic details about this room category')
                                    ->schema([
                                        Grid::make()
                                            ->schema([
                                                Forms\Components\TextInput::make('name')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->columnSpan(['md' => 4]),
                                                
                                                Forms\Components\Toggle::make('is_active')
                                                    ->label('Active')
                                                    ->default(true)
                                                    ->columnSpan(['md' => 2]),
                                                
                                                Forms\Components\Textarea::make('description')
                                                    ->maxLength(65535)
                                                    ->rows(4)
                                                    ->placeholder('Describe this category of rooms and their common features')
                                                    ->columnSpan(['md' => 6]),
                                            ])
                                            ->columns(6),
                                    ]),
                            ]),
                        
                        Tab::make('Booking Policy')
                            ->icon('heroicon-o-clock')
                            ->schema([
                                Forms\Components\Section::make('Default Booking Policy')
                                    ->description('Set the default booking policy for all rooms in this category. Individual rooms can override these settings if needed.')
                                    ->schema([
                                        BookingPolicyForm::make(),
                                    ]),
                            ]),
                    ])
                    ->persistTabInQueryString()
                    ->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
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
                Tables\Filters\Filter::make('is_active')
                    ->toggle()
                    ->label('Show only active categories')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true)),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            RelationManagers\RoomsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoomCategories::route('/'),
            'create' => Pages\CreateRoomCategory::route('/create'),
            'edit' => Pages\EditRoomCategory::route('/{record}/edit'),
        ];
    }
} 