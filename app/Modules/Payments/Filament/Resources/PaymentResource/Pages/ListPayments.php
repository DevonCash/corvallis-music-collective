<?php

namespace App\Modules\Payments\Filament\Resources\PaymentResource\Pages;

use App\Modules\Payments\Filament\Resources\PaymentResource;
use App\Modules\Payments\Models\States\PaymentState\Failed;
use App\Modules\Payments\Models\States\PaymentState\Paid;
use App\Modules\Payments\Models\States\PaymentState\Pending;
use App\Modules\Payments\Models\States\PaymentState\Refunded;
use AlpineIO\Filament\ModelStates\StateTableAction;
use AlpineIO\Filament\ModelStates\StateColumn;
use App\Modules\Payments\Models\Payment;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
    
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->searchable(),
                    
                TextColumn::make('user.name')
                    ->description(fn($record) => $record->user->email ?? '')
                    ->sortable()
                    ->searchable()
                    ->label('User'),
                    
                TextColumn::make('product.name')
                    ->sortable()
                    ->searchable()
                    ->label('Product'),
                    
                TextColumn::make('amount')
                    ->money('usd')
                    ->sortable()
                    ->label('Amount'),
                    
                TextColumn::make('payable_type')
                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                    ->sortable()
                    ->label('Type'),
                    
                TextColumn::make('payable_id')
                    ->sortable()
                    ->label('ID'),
                    
                StateColumn::make('state')
                    ->badge()
                    ->label('Status'),
                    
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Created At'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('state')
                    ->options([
                        Pending::class => 'Pending',
                        Paid::class => 'Paid',
                        Failed::class => 'Failed',
                        Refunded::class => 'Refunded',
                    ])
                    ->label('Payment Status'),
                    
                Tables\Filters\SelectFilter::make('method')
                    ->options([
                        'stripe' => 'Stripe',
                        'cash' => 'Cash',
                        'bank_transfer' => 'Bank Transfer',
                    ])
                    ->label('Payment Method'),
            ])
            ->actions([
                StateTableAction::make('mark_as_paid')
                    ->label('Mark as Paid')
                    ->transitionTo(Paid::class)
                    ->visible(fn (Payment $payment) => $payment->state instanceof Pending),
                    
                StateTableAction::make('mark_as_failed')
                    ->label('Mark as Failed')
                    ->transitionTo(Failed::class)
                    ->visible(fn (Payment $payment) => $payment->state instanceof Pending),
                    
                StateTableAction::make('refund')
                    ->label('Refund')
                    ->transitionTo(Refunded::class)
                    ->visible(fn (Payment $payment) => $payment->state instanceof Paid),
                    
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
} 