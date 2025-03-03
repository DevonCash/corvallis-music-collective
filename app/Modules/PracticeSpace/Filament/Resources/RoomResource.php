<?php

namespace App\Modules\PracticeSpace\Filament\Resources;

use App\Modules\Payments\Models\Product;
use App\Modules\PracticeSpace\Filament\Resources\RoomResource\Pages;
use App\Modules\PracticeSpace\Filament\Resources\RoomResource\RelationManagers;
use App\Modules\PracticeSpace\Models\Room;
use Filament\Forms;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RoomResource extends Resource
{
    protected static ?string $model = Room::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    
    protected static ?string $navigationGroup = 'Practice Space';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label('Room Name'),
                    
                Select::make('product_id')
                    ->relationship('product', 'name')
                    ->required()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('description')
                            ->required()
                            ->maxLength(1000),
                        KeyValue::make('prices')
                            ->keyLabel('Type')
                            ->valueLabel('Price Details')
                            ->keyPlaceholder('e.g. hourly, monthly')
                            ->valuePlaceholder('{"amount": 1500, "currency": "usd"}')
                            ->addable()
                            ->required(),
                    ])
                    ->label('Associated Product'),
                    
                Textarea::make('description')
                    ->required()
                    ->maxLength(1000)
                    ->label('Description'),
                    
                TextInput::make('capacity')
                    ->numeric()
                    ->minValue(1)
                    ->label('Capacity'),
                    
                KeyValue::make('amenities')
                    ->keyLabel('Amenity')
                    ->valueLabel('Description')
                    ->keyPlaceholder('e.g. PA System, Drumkit')
                    ->valuePlaceholder('Description of the amenity')
                    ->addable()
                    ->label('Amenities'),
                    
                KeyValue::make('hours')
                    ->keyLabel('Day')
                    ->valueLabel('Hours')
                    ->keyPlaceholder('e.g. Monday, Tuesday')
                    ->valuePlaceholder('{"open": "09:00", "close": "22:00"}')
                    ->addable()
                    ->label('Operating Hours'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->searchable(),
                    
                TextColumn::make('name')
                    ->sortable()
                    ->searchable()
                    ->label('Room Name'),
                    
                TextColumn::make('product.name')
                    ->sortable()
                    ->searchable()
                    ->label('Product'),
                    
                TextColumn::make('capacity')
                    ->sortable()
                    ->label('Capacity'),
                    
                TextColumn::make('description')
                    ->limit(50)
                    ->searchable()
                    ->label('Description'),
                    
                TextColumn::make('hourly_rate')
                    ->getStateUsing(function (Room $record) {
                        if (!$record->product || !isset($record->product->prices['hourly']['amount'])) {
                            return 'N/A';
                        }
                        
                        $amount = $record->product->prices['hourly']['amount'] / 100; // Convert cents to dollars
                        $currency = $record->product->prices['hourly']['currency'] ?? 'USD';
                        
                        return "\${$amount} {$currency}";
                    })
                    ->label('Hourly Rate'),
                    
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Created At'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('product_id')
                    ->relationship('product', 'name')
                    ->label('Product'),
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
            RelationManagers\BookingsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRooms::route('/'),
            'create' => Pages\CreateRoom::route('/create'),
            'edit' => Pages\EditRoom::route('/{record}/edit'),
            'view' => Pages\ViewRoom::route('/{record}'),
        ];
    }
}
