<?php

namespace App\Modules\Payments\Filament\Resources\ProductResource\Pages;

use App\Modules\Payments\Filament\Resources\ProductResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                    
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->label('Price (in cents)'),
                    
                RichEditor::make('description')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                    
                Toggle::make('is_active')
                    ->required()
                    ->default(true),
                    
                Select::make('product_type')
                    ->options([
                        'booking' => 'Booking',
                        'membership' => 'Membership',
                        'service' => 'Service',
                        'merchandise' => 'Merchandise',
                    ])
                    ->required(),
            ]);
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
} 