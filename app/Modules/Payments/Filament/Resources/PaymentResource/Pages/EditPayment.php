<?php

namespace App\Modules\Payments\Filament\Resources\PaymentResource\Pages;

use App\Modules\Payments\Filament\Resources\PaymentResource;
use App\Modules\Payments\Models\States\PaymentState\Failed;
use App\Modules\Payments\Models\States\PaymentState\Paid;
use App\Modules\Payments\Models\States\PaymentState\Pending;
use App\Modules\Payments\Models\States\PaymentState\Refunded;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;

class EditPayment extends EditRecord
{
    protected static string $resource = PaymentResource::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label('User'),
                    
                Select::make('product_id')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label('Product'),
                    
                Select::make('payable_type')
                    ->options([
                        'App\\Modules\\PracticeSpace\\Models\\Booking' => 'Booking',
                        // Add other payable types as needed
                    ])
                    ->required()
                    ->label('Payable Type'),
                    
                TextInput::make('payable_id')
                    ->numeric()
                    ->required()
                    ->label('Payable ID'),
                    
                TextInput::make('amount')
                    ->numeric()
                    ->required()
                    ->label('Amount (in cents)'),
                    
                TextInput::make('stripe_payment_intent_id')
                    ->label('Stripe Payment Intent ID'),
                    
                Select::make('method')
                    ->options([
                        'stripe' => 'Stripe',
                        'cash' => 'Cash',
                        'bank_transfer' => 'Bank Transfer',
                    ])
                    ->required()
                    ->label('Payment Method'),
                    
                Select::make('state')
                    ->options([
                        Pending::class => 'Pending',
                        Paid::class => 'Paid',
                        Failed::class => 'Failed',
                        Refunded::class => 'Refunded',
                    ])
                    ->required()
                    ->label('Payment State'),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
} 