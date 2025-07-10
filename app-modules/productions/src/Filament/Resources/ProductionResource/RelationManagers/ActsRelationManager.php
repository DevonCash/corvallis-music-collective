<?php

namespace CorvMC\Productions\Filament\Resources\ProductionResource\RelationManagers;

use Livewire\Features\SupportFileUploads\FileUploadController;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ActsRelationManager extends RelationManager
{
  protected static string $relationship = 'acts';

  protected static ?string $recordTitleAttribute = 'name';

  public function form(Form $form): Form
  {
    return $form
      ->columns(1)
      ->schema([
        Forms\Components\Grid::make(2)
          ->schema([
            Forms\Components\TextInput::make('name')
              ->required()
              ->maxLength(255)
              ->columnSpanFull(),
            Forms\Components\Textarea::make('description')
              ->columnSpanFull(),
          ]),
        Forms\Components\Tabs::make('tabs')
          ->tabs([
            Forms\Components\Tabs\Tab::make('Booking Details')
              ->schema([
                Forms\Components\TextInput::make('order')
                  ->label('Performance Order')
                  ->numeric()
                  ->default(fn () => $this->getOwnerRecord()->acts()->count() + 1)
                  ->required(),
                Forms\Components\TextInput::make('set_length')
                  ->label('Set Length (minutes)')
                  ->numeric()
                  ->suffix('minutes'),
                Forms\Components\Textarea::make('notes')
                  ->label('Notes')
                  ->helperText('Any special requirements or notes for this act')
                  ->columnSpanFull(),
              ]),
            Forms\Components\Tabs\Tab::make('Links')
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
                  ->addActionLabel('Add Link')
                  ->helperText('Add website, social media profiles, or other online presence'),
              ]),
            Forms\Components\Tabs\Tab::make('Contact Info')
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

  public function table(Table $table): Table
  {
    return $table
      ->recordTitleAttribute('name')
      ->reorderable('order')
      ->defaultSort('order', 'asc')
      ->columns([
            Tables\Columns\TextColumn::make('name')->searchable()
                ->grow(true)
                ->label('Name'),
            Tables\Columns\TextInputColumn::make('pivot.set_length')
                ->label('Set Length (min)')
                ->grow(false)
                ->type('number')
                ->sortable(),
      ])
      ->filters([
        //
      ])
      ->headerActions([
        Tables\Actions\AttachAction::make()
          ->label('Add Existing Act')
          ->form(fn (Tables\Actions\AttachAction $action): array => [
            $action->getRecordSelect()
              ->searchable()
              ->preload()
              ->getSearchResultsUsing(fn (string $search): array => \CorvMC\Productions\Models\Act::where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id')->toArray())
              ->getOptionLabelUsing(fn ($value): ?string => \CorvMC\Productions\Models\Act::find($value)?->name),
            Forms\Components\TextInput::make('order')
              ->label('Performance Order')
              ->numeric()
              ->default(fn () => $this->getOwnerRecord()->acts()->count() + 1)
              ->required(),
            Forms\Components\TextInput::make('set_length')
              ->label('Set Length (minutes)')
              ->numeric()
              ->suffix('minutes'),
            Forms\Components\Textarea::make('notes')
              ->label('Notes')
              ->helperText('Any special requirements or notes for this act'),
          ])
          ->modalWidth('lg'),
        Tables\Actions\CreateAction::make()
          ->label('Create New Act')
          ->modalWidth('xl'),
      ])
      ->actions([
        Tables\Actions\EditAction::make()
          ->modalWidth('xl'),
        Tables\Actions\DetachAction::make(),
      ])
      ->bulkActions([
        Tables\Actions\BulkActionGroup::make([
          Tables\Actions\DetachBulkAction::make(),
        ]),
      ]);
  }
}
