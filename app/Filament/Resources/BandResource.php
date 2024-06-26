<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BandResource\Pages;
use App\Filament\Resources\BandResource\RelationManagers;
use App\Models\Band;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BandResource extends Resource
{
    protected static ?string $model = Band::class;

    protected static ?string $navigationIcon = "heroicon-o-rectangle-stack";

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make("name")
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make("logo")->maxLength(255),
            Forms\Components\Textarea::make("description"),
            Forms\Components\Repeater::make("links")->schema([
                Forms\Components\TextInput::make("title")
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make("url")
                    ->required()
                    ->maxLength(255),
            ]),
            Forms\Components\DateTimePicker::make("published_at"),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make("name")->searchable(),
                Tables\Columns\TextColumn::make("logo")->searchable(),
                Tables\Columns\TextColumn::make("published_at")
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make("deleted_at")
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
            "index" => Pages\ListBands::route("/"),
            "create" => Pages\CreateBand::route("/create"),
            "edit" => Pages\EditBand::route("/{record}/edit"),
        ];
    }
}
