<?php

namespace CorvMC\Productions\Filament\Resources;

use CorvMC\Productions\Filament\Resources\ProductionResource\Pages;
use CorvMC\Productions\Models\Production;
use CorvMC\Productions\Filament\Resources\VenueResource;
use CorvMC\StateManagement\Filament\Table\Columns\StateColumn;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use CorvMC\Productions\Filament\Resources\VenueRelationManager;
use Illuminate\Support\Facades\Storage;
use CorvMC\Productions\Filament\Resources\ProductionResource\RelationManagers\ActsRelationManager;
use Filament\Notifications\Notification;

class ProductionResource extends Resource
{
    protected static ?string $model = Production::class;

    protected static ?string $navigationIcon = 'heroicon-o-musical-note';

    protected static ?string $navigationGroup = 'Productions';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                Forms\Components\Tabs::make('tabs')
                    ->contained(false)
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Details')
                            ->columns(12)
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->columnSpan(8)
                                    ->schema([
                                        Forms\Components\TextInput::make('title')
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpanFull()
                                            ->extraFieldWrapperAttributes(['class' => 'public']),
                                        Forms\Components\RichEditor::make('description')
                                            ->columnSpanFull()
                                            ->extraFieldWrapperAttributes(['class' => 'public']),

                                        Forms\Components\TextInput::make('ticket_link')
                                            ->label('Ticket Link')
                                            ->url()
                                            ->placeholder('https://...')
                                            ->nullable()
                                            ->extraFieldWrapperAttributes(['class' => 'public']),
                                        Forms\Components\DateTimePicker::make('start_date')
                                            ->label('Start Time')
                                            ->seconds(false)
                                            ->extraFieldWrapperAttributes(['class' => 'public']),
                                        Forms\Components\DateTimePicker::make('end_date')
                                            ->seconds(false)
                                            ->label('End Time')
                                            ->extraFieldWrapperAttributes(['class' => 'public']),
                                    ]),

                                Forms\Components\Grid::make(1)
                                    ->columnSpan(4)
                                    ->schema([
                                        Forms\Components\Select::make('production_lead_id')
                                            ->label('Production Lead')
                                            ->relationship('productionLead', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->createOptionForm([
                                                Forms\Components\TextInput::make('name')
                                                    ->required()
                                                    ->maxLength(255),
                                                Forms\Components\TextInput::make('email')
                                                    ->email()
                                                    ->required()
                                                    ->maxLength(255),
                                            ]),
                                        Forms\Components\FileUpload::make('poster')
                                            ->image()
                                            ->disk('r2')
                                            ->directory('productions')
                                            ->imageResizeMode('cover')
                                            ->imageCropAspectRatio('1:1.294')
                                            ->imageResizeTargetWidth('850')
                                            ->imageResizeTargetHeight('1100')
                                            ->imagePreviewHeight('250')
                                            ->panelLayout('integrated')
                                            ->deleteUploadedFileUsing(function ($file) {
                                                Storage::disk('public')->delete($file);
                                            })
                                            ->label('Event Poster')
                                            ->helperText('Letter size: 8.5" x 11"')
                                            ->extraFieldWrapperAttributes(['class' => 'public poster-field'])
                                            ->columnSpan(1),
                                        Forms\Components\Select::make('tags')
                                            ->relationship('tags', 'name')
                                            ->multiple()
                                            ->preload()
                                            ->searchable()
                                            ->extraFieldWrapperAttributes(['class' => 'public'])
                                            ->createOptionForm([
                                                Forms\Components\TextInput::make('name')
                                                    ->required()
                                                    ->maxLength(255),
                                            ]),

                                    ]),
                            ]),
                        Forms\Components\Tabs\Tab::make('Post-Show Wrap Up')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\Section::make('Attendance & Revenue')
                                            ->schema([
                                                Forms\Components\TextInput::make('wrap_up_data.total_attendance')
                                                    ->label('Total Attendance')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->helperText('Total number of people who attended'),
                                                Forms\Components\TextInput::make('wrap_up_data.door_donations')
                                                    ->label('Door Donations')
                                                    ->numeric()
                                                    ->prefix('$')
                                                    ->minValue(0)
                                                    ->helperText('Donations collected at the door (goes to bands)'),
                                                Forms\Components\TextInput::make('wrap_up_data.counter_donations')
                                                    ->label('Counter Donations')
                                                    ->numeric()
                                                    ->prefix('$')
                                                    ->minValue(0)
                                                    ->helperText('Donations collected at the counter'),
                                                Forms\Components\TextInput::make('wrap_up_data.concessions_sales')
                                                    ->label('Concessions Sales')
                                                    ->numeric()
                                                    ->prefix('$')
                                                    ->minValue(0)
                                                    ->helperText('Revenue from concessions sales'),
                                            ]),
                                        Forms\Components\Section::make('Notes')
                                            ->schema([
                                                Forms\Components\Textarea::make('wrap_up_data.notes')
                                                    ->label('Wrap Up Notes')
                                                    ->helperText('Any additional notes about the production')
                                                    ->columnSpanFull(),
                                            ]),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('poster')
                    ->circular()
                    ->width(40)
                    ->height(40),
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('venue.name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ticket_link')
                    ->label('Tickets')
                    ->url(fn(?Production $record): ?string => $record?->ticket_link)
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-ticket')
                    ->searchable(),
                StateColumn::make('status'),
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
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'planning' => 'Planning',
                        'published' => 'Published',
                        'active' => 'Active',
                        'finished' => 'Finished',
                        'archived' => 'Archived',
                        'rescheduled' => 'Rescheduled',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('transition')
                        ->label('Change Status')
                        ->icon('heroicon-o-arrow-path')
                        ->form(fn(?Production $record) => $record?->state->getForm())
                        ->action(function (?Production $record, array $data): void {
                            $record?->state->transitionTo($data['state'], $data);
                        }),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('create')
                    ->label('New Production')
                    ->icon('heroicon-o-plus')
                    ->action(function () {
                        $production = Production::create([
                            'title' => 'New Production',
                            'state' => 'planning',
                        ]);

                        return redirect()->route('filament.admin.resources.productions.edit', ['record' => $production]);
                    }),
                Tables\Actions\Action::make('publish')
                    ->label('Publish Production')
                    ->icon('heroicon-o-globe-alt')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Production $record): void {
                        try {
                            $record->state->transitionTo('published', [
                                'ready_to_publish' => true,
                            ]);
                            Notification::make()
                                ->title('Production published successfully')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Cannot publish production')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn(?Production $record): bool => $record?->state->getName() === 'planning'),
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
            VenueRelationManager::class,
            ActsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductions::route('/'),
            'edit' => Pages\EditProduction::route('/{record}/edit'),
        ];
    }
}
