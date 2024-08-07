<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PostResource\Pages;
use App\Filament\Admin\Resources\PostResource\RelationManagers;
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
        return $form->columns(2)->schema([
            Components\TextInput::make("title")->required()->maxLength(255),
            Components\TagsInput::make("tags"),
            Components\RichEditor::make("content")
                ->columnSpanFull()
                ->fileAttachmentsDisk("s3")
                ->fileAttachmentsDirectory("attachments")
                ->required(),
            Components\Section::make("Meta")
                ->collapsed()
                ->schema([
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
                ]),
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
