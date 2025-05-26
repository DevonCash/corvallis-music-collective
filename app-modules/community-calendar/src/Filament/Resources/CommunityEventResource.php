<?php

namespace CorvMC\CommunityCalendar\Filament\Resources;

use CorvMC\CommunityCalendar\Filament\Resources\CommunityEventResource\Pages;
use CorvMC\CommunityCalendar\Models\CommunityEvent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CommunityEventResource extends Resource
{
    protected static ?string $model = CommunityEvent::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_url')
                    ->defaultImageUrl('https://picsum.photos/120')
                    ->label('Poster'),

                TextColumn::make('name')
                    ->sortable()
                    ->searchable()
                    ->placeholder('Unnamed Event'),
                TextColumn::make('start_date')
                    ->sortable()
                    ->searchable()
                    ->dateTime('M d, Y h:i A')
                    ->placeholder('No Start Date'),
                TextColumn::make('location_name')
                    ->sortable()
                    ->searchable()
                    ->placeholder('No Location'),
            ])
            ->filters([
                //
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCommunityEvents::route('/'),
            'edit' => Pages\EditCommunityEvent::route('/{record}/edit'),
        ];
    }
}
