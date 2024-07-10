<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EventResource\Pages;
use App\Filament\Resources\EventResource\RelationManagers;
use App\Models\Event;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static ?string $navigationIcon = "heroicon-o-calendar-days";

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make("name")
                ->required()
                ->maxLength(255),
            Forms\Components\Textarea::make("description")
                ->required()
                ->columnSpanFull(),
            Forms\Components\TextInput::make("links")->required(),
            Forms\Components\DateTimePicker::make("door_time"),
            Forms\Components\DateTimePicker::make("start_time")->required(),
            Forms\Components\DateTimePicker::make("end_time"),
            Forms\Components\TextInput::make("price"),
            Forms\Components\TextInput::make("venue_id")->numeric(),
            Forms\Components\DateTimePicker::make("published_at"),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make("name")->searchable(),
                Tables\Columns\TextColumn::make("door_time")
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make("start_time")
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make("end_time")
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make("venue_id")
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make("published_at")
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make("created_at")
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make("updated_at")
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([Tables\Actions\EditAction::make()])
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
            "index" => Pages\ListEvents::route("/"),
            "create" => Pages\CreateEvent::route("/create"),
            "edit" => Pages\EditEvent::route("/{record}/edit"),
        ];
    }
}
