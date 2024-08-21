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
use App\Models\User;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static ?string $navigationIcon = "heroicon-o-newspaper";

    public static function form(Form $form): Form
    {
        return $form->columns(1)->schema([
            Components\TextInput::make("title")->required()->maxLength(255),
            Components\TagsInput::make("tags"),
            Components\RichEditor::make("content")
                ->columnSpanFull()
                ->fileAttachmentsDisk("s3")
                ->fileAttachmentsDirectory("attachments")
                ->required(),

            TableRepeater::make('postUsers')
                ->relationship()
                ->headers([
                    Header::make('User')
                ])
                ->streamlined()
                ->orderable()
                ->orderColumn('order')
                ->schema([
                    Components\Select::make('user_id')
                        ->relationship('user', 'name')
                        ->required(),
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
