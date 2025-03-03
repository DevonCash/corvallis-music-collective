<?php

namespace App\Modules\Payments\Filament\Resources;

use App\Modules\Payments\Filament\Resources\ProductResource\Pages;
use App\Modules\Payments\Models\Product;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    
    protected static ?string $navigationGroup = 'Finance';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label('Product Name'),
                    
                Textarea::make('description')
                    ->required()
                    ->maxLength(1000)
                    ->label('Description'),
                    
                TextInput::make('stripe_product_id')
                    ->label('Stripe Product ID'),
                    
                Toggle::make('is_visible')
                    ->label('Visible')
                    ->helperText('Should this product be visible to customers?')
                    ->default(true),
                    
                Select::make('subscription_interval')
                    ->options([
                        null => 'One-time payment',
                        'day' => 'Daily',
                        'week' => 'Weekly',
                        'month' => 'Monthly',
                        'year' => 'Yearly',
                    ])
                    ->default(null)
                    ->label('Subscription Interval'),
                    
                KeyValue::make('prices')
                    ->keyLabel('Type')
                    ->valueLabel('Price Details')
                    ->keyPlaceholder('e.g. hourly, monthly')
                    ->valuePlaceholder('{"amount": 1500, "currency": "usd"}')
                    ->addable()
                    ->required()
                    ->label('Pricing Structure'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->searchable(),
                    
                TextColumn::make('name')
                    ->sortable()
                    ->searchable()
                    ->label('Product Name'),
                    
                TextColumn::make('description')
                    ->limit(50)
                    ->searchable()
                    ->label('Description'),
                    
                TextColumn::make('prices')
                    ->formatStateUsing(function ($state) {
                        if (!is_array($state) || empty($state)) return 'No prices';
                        
                        $priceDetails = [];
                        foreach ($state as $type => $price) {
                            if (isset($price['amount'])) {
                                $amount = $price['amount'] / 100; // Convert cents to dollars
                                $currency = $price['currency'] ?? 'USD';
                                $priceDetails[] = "{$type}: \${$amount} {$currency}";
                            }
                        }
                        
                        return implode(', ', $priceDetails);
                    })
                    ->label('Prices'),
                    
                ToggleColumn::make('is_visible')
                    ->label('Visible'),
                    
                TextColumn::make('subscription_interval')
                    ->formatStateUsing(fn ($state) => $state ? ucfirst($state) : 'One-time')
                    ->label('Billing Type'),
                    
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Created At'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('subscription_interval')
                    ->options([
                        '' => 'One-time payment',
                        'day' => 'Daily',
                        'week' => 'Weekly',
                        'month' => 'Monthly',
                        'year' => 'Yearly',
                    ])
                    ->label('Subscription Type'),
                    
                Tables\Filters\TernaryFilter::make('is_visible')
                    ->label('Visibility'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
            'view' => Pages\ViewProduct::route('/{record}'),
        ];
    }
} 