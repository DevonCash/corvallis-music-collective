<?php

namespace App\Modules\Payments\Filament\Resources\ProductResource\Pages;

use App\Modules\Payments\Filament\Resources\ProductResource;
use App\Modules\Payments\Models\Product;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Columns\IconColumn;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

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
                    
                TextColumn::make('price')
                    ->money('usd')
                    ->sortable(),
                    
                TextColumn::make('product_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'booking' => 'success',
                        'membership' => 'info',
                        'service' => 'warning',
                        'merchandise' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                    
                ToggleColumn::make('is_active')
                    ->sortable(),
                    
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('product_type')
                    ->options([
                        'booking' => 'Booking',
                        'membership' => 'Membership',
                        'service' => 'Service',
                        'merchandise' => 'Merchandise',
                    ]),
                    
                Tables\Filters\TernaryFilter::make('is_active'),
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