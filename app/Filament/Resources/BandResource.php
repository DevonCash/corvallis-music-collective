<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BandResource\Pages;
use App\Filament\Resources\BandResource\RelationManagers;
use App\Models\Band;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;

class BandResource extends Resource
{
    protected static ?string $model = Band::class;

    protected static ?string $navigationIcon = "heroicon-o-musical-note";

    public static function form(Form $form): Form
    {
        return $form->schema([
            Components\Split::make([
                Components\Tabs::make()
                    ->tabs([
                        Components\Tabs\Tab::make("Band Profile")->schema([
                            Components\Grid::make()
                                ->columns(2)
                                ->schema([
                                    Components\TextInput::make("name")
                                        ->required()
                                        ->maxLength(255),
                                    Components\TextInput::make(
                                        "home_city"
                                    )->maxLength(255),
                                ]),

                            Components\RichEditor::make("description")
                                ->required()
                                ->columnSpanFull(),
                            TableRepeater::make("links")
                                ->headers([
                                    Header::make("Label"),
                                    Header::make("URL")->width("66%"),
                                ])
                                ->schema([
                                    Components\TextInput::make("label"),
                                    Components\TextInput::make("url"),
                                ])
                                ->streamlined(),
                        ]),
                        Components\Tabs\Tab::make("Manage Members")->schema([]),
                    ])
                    ->columnSpanFull(),
                Components\Grid::make()
                    ->columns(1)
                    ->schema([Components\TagsInput::make("tags")])
                    ->grow(false),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make("name")->searchable(),
                Tables\Columns\TextColumn::make("created_at")
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make("updated_at")
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make("deleted_at")
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make("published_at")
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make("home_city")->searchable(),
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
        return [];
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
