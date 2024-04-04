<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageResource\Pages;
use App\Filament\Resources\PageResource\RelationManagers;
use App\Models\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static ?string $navigationIcon = "heroicon-o-rectangle-stack";

    public static function form(Form $form): Form
    {
        return $form->schema([
            \Filament\Forms\Components\Tabs::make("Tabs")->tabs([
                \Filament\Forms\Components\Tabs\Tab::make("Template"),
                \Filament\Forms\Components\Tabs\Tab::make("Content"),
                \Filament\Forms\Components\Tabs\Tab::make("Config"),
            ]),
        ]);

        return $form->schema([
            Forms\Components\TextInput::make("name")
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make("route")
                ->required()
                ->maxLength(255),
            Forms\Components\Textarea::make("template")
                ->required()
                ->columnSpanFull(),
            Forms\Components\TextInput::make("localization")->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make("name")->searchable(),
                Tables\Columns\TextColumn::make("route")->searchable(),
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
            "index" => Pages\ListPages::route("/"),
            "create" => Pages\CreatePage::route("/create"),
            "edit" => Pages\EditPage::route("/{record}/edit"),
        ];
    }
}
