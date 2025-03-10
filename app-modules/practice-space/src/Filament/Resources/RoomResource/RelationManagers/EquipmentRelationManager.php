<?php

namespace CorvMC\PracticeSpace\Filament\Resources\RoomResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EquipmentRelationManager extends RelationManager
{
    protected static string $relationship = 'equipment';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $title = 'Room Equipment';

    protected static ?string $icon = 'heroicon-o-musical-note';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Equipment Details')
                    ->description('Add or edit equipment available in this room')
                    ->schema([
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g., Yamaha Upright Piano, Drum Kit')
                                    ->columnSpan(['md' => 6]),
                                
                                Forms\Components\Select::make('condition')
                                    ->options([
                                        'excellent' => 'Excellent',
                                        'good' => 'Good',
                                        'fair' => 'Fair',
                                        'poor' => 'Poor',
                                        'needs_repair' => 'Needs Repair',
                                    ])
                                    ->default('good')
                                    ->required()
                                    ->columnSpan(['md' => 6]),
                                
                                Forms\Components\Textarea::make('description')
                                    ->placeholder('Describe the equipment, including model, specifications, and any special features')
                                    ->rows(3)
                                    ->columnSpanFull(),
                                
                                Forms\Components\TextInput::make('quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required()
                                    ->columnSpan(['md' => 6]),
                                
                                Forms\Components\DatePicker::make('last_maintenance_date')
                                    ->label('Last Maintenance Date')
                                    ->placeholder('Select date')
                                    ->columnSpan(['md' => 6]),
                                
                                Forms\Components\Toggle::make('is_available')
                                    ->label('Available for use')
                                    ->helperText('Toggle off if equipment is temporarily unavailable')
                                    ->default(true)
                                    ->columnSpanFull(),
                            ])
                            ->columns(12),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->description ? \Illuminate\Support\Str::limit($record->description, 50) : null),
                
                Tables\Columns\BadgeColumn::make('condition')
                    ->colors([
                        'success' => 'excellent',
                        'primary' => 'good',
                        'warning' => 'fair',
                        'danger' => ['poor', 'needs_repair'],
                    ]),
                
                Tables\Columns\TextColumn::make('quantity')
                    ->sortable()
                    ->alignCenter(),
                
                Tables\Columns\IconColumn::make('is_available')
                    ->label('Available')
                    ->boolean()
                    ->alignCenter(),
                
                Tables\Columns\TextColumn::make('last_maintenance_date')
                    ->date()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('condition')
                    ->options([
                        'excellent' => 'Excellent',
                        'good' => 'Good',
                        'fair' => 'Fair',
                        'poor' => 'Poor',
                        'needs_repair' => 'Needs Repair',
                    ]),
                
                Tables\Filters\TernaryFilter::make('is_available')
                    ->label('Availability')
                    ->placeholder('All equipment')
                    ->trueLabel('Available equipment only')
                    ->falseLabel('Unavailable equipment only'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Equipment')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Add New Equipment'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalHeading('Edit Equipment')
                    ->slideOver(),
                Tables\Actions\DeleteAction::make()
                    ->modalHeading('Delete Equipment')
                    ->modalDescription('Are you sure you want to delete this equipment? This action cannot be undone.'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No equipment added yet')
            ->emptyStateDescription('Add equipment that is available in this room')
            ->emptyStateIcon('heroicon-o-musical-note')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Equipment')
                    ->icon('heroicon-o-plus'),
            ]);
    }
} 