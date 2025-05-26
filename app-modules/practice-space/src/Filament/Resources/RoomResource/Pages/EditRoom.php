<?php

namespace CorvMC\PracticeSpace\Filament\Resources\RoomResource\Pages;

use CorvMC\PracticeSpace\Filament\Resources\RoomResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms;
use Filament\Forms\Components\{Section, Tabs, Grid, TextInput, Select, Toggle, FileUpload, Textarea};
use CorvMC\PracticeSpace\Filament\Forms\Components\BookingPolicyForm;


class EditRoom extends EditRecord
{
    protected static string $resource = RoomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Delete Room')
                ->modalDescription('Are you sure you want to delete this room? This action cannot be undone and may affect existing bookings.')
                ->modalSubmitActionLabel('Yes, delete room')
                ->modalCancelActionLabel('No, keep room')
                ->color('danger'),

            Actions\Action::make('toggle_active')
                ->label(fn($record) => $record->is_active ? 'Disable Booking' : 'Enable Booking')
                ->action(fn($record) => $record->update(['is_active' => !$record->is_active]))
                ->requiresConfirmation()
                ->outlined(true)
                ->color(fn($record) => $record->is_active ? 'danger' : 'success'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Tabs::make('Room')
                    ->contained(false)
                    ->tabs([
                    Tabs\Tab::make('Details')

                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            Section::make('Basic Information')
                                ->description('Enter the basic details about this room')
                                ->columns(12)
                                ->schema([
                                    TextInput::make('name')
                                        ->required()
                                        ->maxLength(255)
                                        ->placeholder('e.g., Studio A, Practice Room 101')
                                        ->columnSpan(['sm' => 6,'md'=>8]),

                                    Select::make('room_category_id')
                                        ->label('Category')
                                        ->relationship('category', 'name')
                                        ->createOptionForm([
                                            TextInput::make('name')
                                                ->required()
                                                ->maxLength(255),
                                            Textarea::make('description')
                                                ->maxLength(65535),
                                            Toggle::make('is_active')
                                                ->label('Active')
                                                ->default(true),
                                        ])
                                        ->searchable()
                                        ->preload()
                                        ->columnSpan(['sm' => 6,'md'=>4]),
                                    Textarea::make('description')
                                        ->maxLength(65535)
                                        ->rows(3)
                                        ->placeholder('Describe the room, its features, and what it\'s best used for')
                                        ->helperText('Markdown formatting is supported')
                                        ->columnSpanFull(),
                                ]),

                            Section::make('Room Details')
                                ->description('Provide specific information about the room')
                                ->schema([
                                    Grid::make()
                                        ->schema([
                                            TextInput::make('capacity')
                                                ->required()
                                                ->numeric()
                                                ->minValue(1)
                                                ->suffix('people')
                                                ->placeholder('10')
                                                ->helperText('Maximum number of people allowed')
                                                ->columnSpan(['md' => 6]),

                                            TextInput::make('hourly_rate')
                                                ->required()
                                                ->numeric()
                                                ->prefix('$')
                                                ->minValue(0)
                                                ->step(0.01)
                                                ->placeholder('25.00')
                                                ->helperText('Price per hour for booking this room')
                                                ->columnSpan(['md' => 6]),

                                            TextInput::make('size_sqft')
                                                ->label('Size')
                                                ->numeric()
                                                ->minValue(1)
                                                ->suffix('sq ft')
                                                ->placeholder('400')
                                                ->helperText('Room size in square feet')
                                                ->columnSpan(['md' => 6]),

                                            Select::make('amenities')
                                                ->multiple()
                                                ->options([
                                                    'wifi' => 'WiFi',
                                                    'sound_system' => 'Sound System',
                                                    'projector' => 'Projector',
                                                    'whiteboard' => 'Whiteboard',
                                                    'air_conditioning' => 'Air Conditioning',
                                                    'natural_light' => 'Natural Light',
                                                ])
                                                ->placeholder('Select amenities')
                                                ->helperText('Features available in this room')
                                                ->columnSpan(['md' => 6]),
                                        ])
                                        ->columns(12),
                                ]),
                        ]),

                    Tabs\Tab::make('Media')
                        ->icon('heroicon-o-photo')
                        ->schema([
                            Forms\Components\Section::make('Room Photos')
                                ->description('Upload photos of the room to showcase its features')
                                ->schema([
                                    FileUpload::make('photos')
                                        ->multiple()
                                        ->directory('room-photos')
                                        ->image()
                                        ->imageResizeMode('cover')
                                        ->imageCropAspectRatio('16:9')
                                        ->imageResizeTargetWidth('1920')
                                        ->imageResizeTargetHeight('1080')
                                        ->maxFiles(5)
                                        ->reorderable()
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    Tabs\Tab::make('Specifications')
                        ->icon('heroicon-o-clipboard-document-list')
                        ->schema([
                            Forms\Components\Section::make('Room Specifications')
                                ->description('Add technical specifications and amenities for this room')
                                ->schema([
                                    Forms\Components\KeyValue::make('specifications')
                                        ->keyLabel('Feature')
                                        ->valueLabel('Description')
                                        ->keyPlaceholder('e.g., Size, Equipment, Acoustics')
                                        ->valuePlaceholder('e.g., 500 sq ft, Drum kit included, Soundproofed')
                                        ->addable()
                                        ->reorderable()
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    Tabs\Tab::make('Booking Policy')
                        ->icon('heroicon-o-clock')
                        ->schema([
                            Forms\Components\Section::make('Booking Policy Settings')
                                ->description('Configure the booking policy for this room. If left empty, the room category\'s default policy will be used.')
                                ->schema([
                                    BookingPolicyForm::make(),
                                ]),
                        ]),
                    ])
                    ->columnSpanFull()
            ])
            ->statePath('data')
            ->inlineLabel(false);
    }
}
