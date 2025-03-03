<?php

namespace App\Modules\PracticeSpace\Filament\Resources\RoomResource\Pages;

use App\Modules\PracticeSpace\Filament\Resources\RoomResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\CreateRecord;

class CreateRoom extends CreateRecord
{
    protected static string $resource = RoomResource::class;
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                    
                Select::make('product_id')
                    ->relationship('product', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                    
                RichEditor::make('description')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                    
                TextInput::make('capacity')
                    ->numeric()
                    ->required(),
                    
                TagsInput::make('amenities')
                    ->placeholder('Add amenities')
                    ->columnSpanFull(),
                    
                FileUpload::make('images')
                    ->multiple()
                    ->directory('rooms')
                    ->columnSpanFull(),
                    
                Repeater::make('operating_hours')
                    ->schema([
                        Select::make('day_of_week')
                            ->options([
                                'Monday' => 'Monday',
                                'Tuesday' => 'Tuesday',
                                'Wednesday' => 'Wednesday',
                                'Thursday' => 'Thursday',
                                'Friday' => 'Friday',
                                'Saturday' => 'Saturday',
                                'Sunday' => 'Sunday',
                            ])
                            ->required(),
                            
                        TimePicker::make('opens_at')
                            ->seconds(false)
                            ->required(),
                            
                        TimePicker::make('closes_at')
                            ->seconds(false)
                            ->required(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
} 