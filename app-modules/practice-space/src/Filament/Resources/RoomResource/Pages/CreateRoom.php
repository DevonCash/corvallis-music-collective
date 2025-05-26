<?php

namespace CorvMC\PracticeSpace\Filament\Resources\RoomResource\Pages;

use CorvMC\PracticeSpace\Filament\Resources\RoomResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Forms;
use Filament\Forms\Components\{Section, Grid, TextInput, Select, Toggle, Textarea};

class CreateRoom extends CreateRecord
{
    protected static string $resource = RoomResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Section::make('Basic Information')
                    ->description('Enter the essential details to create this room')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Studio A, Practice Room 101')
                            ->columnSpan(['sm' => 12, 'md' => 6]),

                        Select::make('room_category_id')
                            ->label('Category')
                            ->relationship('category', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->columnSpan(['sm' => 12, 'md' => 6]),

                        TextInput::make('hourly_rate')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->minValue(0)
                            ->step(0.01)
                            ->placeholder('25.00')
                            ->helperText('Price per hour for booking this room')
                            ->columnSpan(['sm' => 12, 'md' => 6]),

                        Toggle::make('is_active')
                            ->label('Make room available for booking immediately')
                            ->default(true)
                            ->columnSpan(12),
                    ]),
            ])
            ->statePath('data')
            ->inlineLabel(false);
    }
}
