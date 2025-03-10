<?php

namespace CorvMC\PracticeSpace\Filament\Resources\RoomResource\Pages;

use CorvMC\PracticeSpace\Filament\Resources\RoomResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms;


class ListRooms extends ListRecords
{
    protected static string $resource = RoomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }



    public function table(Tables\Table $table): Tables\Table
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
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

} 