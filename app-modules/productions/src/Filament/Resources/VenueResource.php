<?php

namespace CorvMC\Productions\Filament\Resources;

use CorvMC\Productions\Filament\Resources\VenueResource\Pages;
use CorvMC\Productions\Models\Venue;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Navigation\NavigationItem;

class VenueResource extends Resource
{
    protected static ?string $model = Venue::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationGroup = 'Productions';

    protected static ?int $navigationSort = 2;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'productions/venues';

    public static function getNavigationItems(): array
    {
        return [
            NavigationItem::make(static::getNavigationLabel())
                ->group(static::getNavigationGroup())
                ->icon(static::getNavigationIcon())
                ->sort(static::getNavigationSort())
                ->url(static::getUrl())
                ->badge(static::getNavigationBadge())
                ->badgeColor(static::getNavigationBadgeColor()),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVenues::route('/'),
            'create' => Pages\CreateVenue::route('/create'),
            'edit' => Pages\EditVenue::route('/{record}/edit'),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('tabs')
                    ->contained(false)
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Details')
                            ->schema([
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->required()
                                            ->columnSpan(2)
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('capacity')
                                            ->numeric()
                                            ->nullable(),

                                    ]),
                                Forms\Components\Textarea::make('description')
                                    ->maxLength(65535),
                                Forms\Components\Fieldset::make('Address')
                                    ->schema([
                                        Forms\Components\TextInput::make('address.street')
                                            ->label('Street Address')
                                            ->required()
                                            ->columnSpanFull(),
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
                            ]),
                        Forms\Components\Tabs\Tab::make('Contact Information')
                            ->columns(2)
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
                    ]),

            ]);
    }

    public static function table(Table $table): Table
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
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }
}
