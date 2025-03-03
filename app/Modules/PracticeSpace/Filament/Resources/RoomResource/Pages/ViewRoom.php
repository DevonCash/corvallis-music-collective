<?php

namespace App\Modules\PracticeSpace\Filament\Resources\RoomResource\Pages;

use App\Modules\PracticeSpace\Filament\Resources\RoomResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ViewRecord;

class ViewRoom extends ViewRecord
{
    protected static string $resource = RoomResource::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->disabled(),
                    
                Select::make('product_id')
                    ->relationship('product', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->disabled(),
                    
                RichEditor::make('description')
                    ->maxLength(65535)
                    ->columnSpanFull()
                    ->disabled(),
                    
                TextInput::make('capacity')
                    ->numeric()
                    ->required()
                    ->disabled(),
                    
                TagsInput::make('amenities')
                    ->placeholder('Add amenities')
                    ->columnSpanFull()
                    ->disabled(),
                    
                FileUpload::make('images')
                    ->multiple()
                    ->directory('rooms')
                    ->columnSpanFull()
                    ->disabled(),
                    
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
                            ->required()
                            ->disabled(),
                            
                        TimePicker::make('opens_at')
                            ->seconds(false)
                            ->required()
                            ->disabled(),
                            
                        TimePicker::make('closes_at')
                            ->seconds(false)
                            ->required()
                            ->disabled(),
                    ])
                    ->columnSpanFull()
                    ->disabled(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
