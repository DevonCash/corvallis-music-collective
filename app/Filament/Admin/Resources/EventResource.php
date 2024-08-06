<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\EventResource\Pages;
use App\Models\Event;
use Awcodes\Curator\Components\Forms\CuratorPicker;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Support\Enums\Alignment;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static ?string $navigationIcon = "heroicon-o-calendar-days";

    public static function form(Form $form): Form
    {
        return $form->schema([
            Components\Split::make([
                Components\Grid::make([])->schema([
                    Components\TextInput::make("name")
                        ->required()
                        ->maxLength(255),
                    Components\Select::make("venue_id")
                        ->relationship("venue", "name")
                        ->createOptionForm([
                            Components\TextInput::make("name")
                                ->required()
                                ->maxLength(255),
                            Components\TextInput::make("link")->maxLength(255),
                        ]),
                    Components\RichEditor::make("description"),
                    TableRepeater::make("links")
                        ->headers([
                            Header::make("Label"),
                            Header::make("URL")->width("66%"),
                        ])
                        ->schema([
                            Components\TextInput::make("label"),
                            Components\TextInput::make("url"),
                        ])
                        ->addActionLabel("Add Link")
                        ->streamlined(),
                    TableRepeater::make("price")
                        ->headers([
                            Header::make("Label"),
                            Header::make("Price"),
                        ])
                        ->schema([
                            Components\TextInput::make("label"),
                            Components\TextInput::make("price"),
                        ])
                        ->addActionLabel("Add Price")
                        ->streamlined(),
                ]),
                Components\Grid::make()
                    ->columns(1)
                    ->extraAttributes(["class" => "fixed-size"])
                    ->schema([
                        CuratorPicker::make("poster_id"),
                        Components\Section::make()->schema([
                            Components\DateTimePicker::make(
                                "door_time"
                            )->seconds(false),
                            Components\DateTimePicker::make("start_time")
                                ->seconds(false)
                                ->required(),
                            Components\DateTimePicker::make(
                                "end_time"
                            )->seconds(false),
                        ]),
                        Components\TagsInput::make("tags")->grow(false),
                    ])
                    ->grow(false),
            ])
                ->from("md")
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make("name")->searchable(),
                Tables\Columns\TextColumn::make("start_time")
                    ->dateTime("D, M j, Y g:i A")
                    ->sortable(),
                Tables\Columns\IconColumn::make("published_at")
                    ->label("Published")
                    ->alignment(Alignment::Center)
                    ->tooltip(
                        fn($record) => $record->published_at ?? "Not Scheduled"
                    )
                    ->icon(function ($record) {
                        if (empty($record->published_at)) {
                            return "heroicon-o-pencil-square";
                        }
                        if ($record->published_at->isFuture()) {
                            return "heroicon-o-calendar";
                        }
                        return "heroicon-o-check-circle";
                    })
                    ->color(function ($record) {
                        if (empty($record->published_at)) {
                            return "gray";
                        }
                        if ($record->published_at->isFuture()) {
                            return "info";
                        }
                        return "success";
                    }),
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
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make("duplicate")
                    ->label("Duplicate")
                    ->icon("heroicon-o-document-duplicate")
                    ->url(
                        fn($record) => "/admin/events/create?from=$record->id"
                    ),
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
            "index" => Pages\ListEvents::route("/"),
            "create" => Pages\CreateEvent::route("/create"),
            "edit" => Pages\EditEvent::route("/{record}/edit"),
        ];
    }
}
