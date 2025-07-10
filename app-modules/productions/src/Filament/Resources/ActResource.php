<?php

namespace CorvMC\Productions\Filament\Resources;

use CorvMC\Productions\Filament\Resources\ActResource\Pages;
use CorvMC\Productions\Models\Act;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ActResource extends Resource
{
    protected static ?string $model = Act::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Productions';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->description('Core details about the act')
                    ->icon('heroicon-o-information-circle')
                    ->collapsible()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->rows(3),
                    ]),
                Forms\Components\Section::make('Social Links')
                    ->description('Online presence and social media')
                    ->icon('heroicon-o-globe-alt')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Repeater::make('social_links')
                            ->label('Websites & Social Media')
                            ->schema([
                                Forms\Components\Select::make('platform')
                                    ->options([
                                        'website' => 'Website',
                                        'facebook' => 'Facebook',
                                        'instagram' => 'Instagram',
                                        'twitter' => 'Twitter',
                                        'youtube' => 'YouTube',
                                        'spotify' => 'Spotify',
                                        'bandcamp' => 'Bandcamp',
                                        'soundcloud' => 'SoundCloud',
                                        'tiktok' => 'TikTok',
                                        'other' => 'Other',
                                    ])
                                    ->required(),
                                Forms\Components\TextInput::make('url')
                                    ->url()
                                    ->required(),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->collapsed()
                            ->itemLabel(fn (array $state): ?string => $state['platform'] ?? 'New Link')
                            ->addActionLabel('Add Link'),
                    ]),
                Forms\Components\Section::make('Contact Information')
                    ->description('How to reach the act')
                    ->icon('heroicon-o-phone')
                    ->collapsible()
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
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),
                Tables\Columns\TextColumn::make('contact_info.name')
                    ->label('Contact')
                    ->searchable(),
                Tables\Columns\TextColumn::make('contact_info.email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('social_links')
                    ->label('Links')
                    ->formatStateUsing(function ($state) {
                        if (!$state || !is_array($state)) return '';
                        $count = count($state);
                        return $count > 0 ? "{$count} link" . ($count > 1 ? 's' : '') : '';
                    }),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActs::route('/'),
            'create' => Pages\CreateAct::route('/create'),
            'edit' => Pages\EditAct::route('/{record}/edit'),
        ];
    }
}