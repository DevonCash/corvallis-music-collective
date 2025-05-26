<?php

namespace CorvMC\Productions\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class VenueRelationManager extends RelationManager
{
    protected static string $relationship = 'venue';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('capacity')
                    ->numeric()
                    ->nullable(),
                Forms\Components\Textarea::make('description')
                    ->maxLength(65535),
                Forms\Components\Fieldset::make('Address')
                    ->schema([
                        Forms\Components\TextInput::make('address.street')
                            ->label('Street Address')
                            ->required(),
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('address.city')
                                    ->required(),
                                Forms\Components\TextInput::make('address.state')
                                    ->required(),
                                Forms\Components\TextInput::make('address.postal_code')
                                    ->label('Postal Code')
                                    ->required(),
                            ]),
                    ]),
                Forms\Components\Fieldset::make('Contact Information')
                    ->schema([
                        Forms\Components\TextInput::make('contact_info.name')
                            ->label('Contact Name')
                            ->required(),
                        Forms\Components\TextInput::make('contact_info.role')
                            ->label('Role/Position')
                            ->required(),
                        Forms\Components\TextInput::make('contact_info.email')
                            ->email()
                            ->required(),
                        Forms\Components\TextInput::make('contact_info.phone')
                            ->tel()
                            ->required(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('address')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('capacity')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
} 