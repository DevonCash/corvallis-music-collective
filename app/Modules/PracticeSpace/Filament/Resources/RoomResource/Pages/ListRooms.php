<?php

namespace App\Modules\PracticeSpace\Filament\Resources\RoomResource\Pages;

use App\Modules\PracticeSpace\Filament\Resources\RoomResource;
use App\Modules\PracticeSpace\Models\Room;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms;

class ListRooms extends ListRecords
{
    protected static string $resource = RoomResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->searchable(),
                    
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('product.name')
                    ->searchable()
                    ->sortable()
                    ->label('Product'),
                    
                TextColumn::make('capacity')
                    ->numeric()
                    ->sortable(),
                    
                TextColumn::make('description')
                    ->limit(50)
                    ->searchable(),
                    
                TextColumn::make('product.price')
                    ->money('usd')
                    ->label('Hourly Rate')
                    ->sortable(),
                    
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('product_id')
                    ->relationship('product', 'name')
                    ->label('Product'),
                    
                Tables\Filters\Filter::make('capacity')
                    ->form([
                        Forms\Components\TextInput::make('min_capacity')
                            ->numeric()
                            ->label('Minimum Capacity'),
                            
                        Forms\Components\TextInput::make('max_capacity')
                            ->numeric()
                            ->label('Maximum Capacity'),
                    ])
                    ->query(function (Tables\Filters\Filter $filter, \Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder {
                        return $query
                            ->when(
                                $filter->getState()['min_capacity'],
                                fn (\Illuminate\Database\Eloquent\Builder $query, $min): \Illuminate\Database\Eloquent\Builder => 
                                    $query->where('capacity', '>=', $min)
                            )
                            ->when(
                                $filter->getState()['max_capacity'],
                                fn (\Illuminate\Database\Eloquent\Builder $query, $max): \Illuminate\Database\Eloquent\Builder => 
                                    $query->where('capacity', '<=', $max)
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
