<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostResource\Pages;
use App\Filament\Resources\PostResource\RelationManagers;
use App\Models\Post;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static ?string $navigationIcon = "heroicon-o-newspaper";

    public static function form(Form $form): Form
    {
        return $form->schema([
            Components\Split::make([
                Components\Section::make([
                    Components\TextInput::make("title")
                        ->required()
                        ->maxLength(255),
                    Components\RichEditor::make("content")->required(),
                ]),
                Components\Grid::make()
                    ->columns(1)
                    ->schema([
                        Components\TagsInput::make("tags"),
                        TableRepeater::make("authors")
                            ->relationship()
                            ->orderColumn("order")
                            ->headers([Header::make("name")])
                            ->schema([
                                Components\Select::make("user_id")
                                    ->relationship("authors", "name")
                                    ->required(),
                                Components\TextInput::make("name")->readOnly(),
                            ])
                            ->streamlined(),
                    ])
                    ->grow(false),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make("title")->searchable(),
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
            "index" => Pages\ListPosts::route("/"),
            "edit" => Pages\EditPost::route("/{record}/edit"),
        ];
    }
}
