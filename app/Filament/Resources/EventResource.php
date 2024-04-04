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

    protected static ?string $navigationIcon = "heroicon-o-rectangle-stack";

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make("name")
                ->required()
                ->maxLength(255),
            Forms\Components\MarkdownEditor::make(
                "description"
            )->columnSpanFull(),
            Forms\Components\DateTimePicker::make("start_at")->required(),
            Forms\Components\DateTimePicker::make("end_at")->required(),
            Forms\Components\TextInput::make("series_id")->numeric(),
            Forms\Components\TextInput::make("location")->maxLength(255),
            Forms\Components\TextInput::make("url")->maxLength(255),
            Forms\Components\FileUpload::make("image")
                ->disk("s3")
                ->directory("images/events")
                ->image(),
            Forms\Components\DateTimePicker::make("published_at"),
            Forms\Components\TextInput::make("links"),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make("name")->searchable(),
                Tables\Columns\TextColumn::make("start_at")
                    ->dateTime("F j, g:i A")
                    ->sortable(),
                Tables\Columns\TextColumn::make("end_at")
                    ->dateTime("F j, g:i A")
                    ->sortable(),

                Tables\Columns\TextColumn::make("location")->searchable(),
                Tables\Columns\TextColumn::make("published_at")
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
