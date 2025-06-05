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
        Forms\Components\TextInput::make('name')
          ->required()
          ->maxLength(255),
        Forms\Components\Textarea::make('description'),
        Forms\Components\Tabs::make('tabs')
          ->tabs([
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
                  ->reorderable(false)
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
      ->reorderable('pivot.order')
      ->defaultSort('pivot.order', 'asc')
      ->columns([
        Tables\Columns\TextColumn::make('name')
          ->searchable(),
        Tables\Columns\TextInputColumn::make('pivot.set_length')
          ->label('Set Length')
          ->type('number')
          ->sortable(),
        Tables\Columns\TextColumn::make('social_links')
          ->label('Websites')
          ->formatStateUsing(function ($state) {
            if (!$state) return null;
            $links = collect($state)->map(function ($link) {
              $icon = match ($link['platform']) {
                'website' => 'heroicon-o-globe-alt',
                'facebook' => 'heroicon-o-globe-alt',
                'instagram' => 'heroicon-o-camera',
                'twitter' => 'heroicon-o-chat-bubble-left-right',
                'youtube' => 'heroicon-o-play',
                'spotify' => 'heroicon-o-musical-note',
                'bandcamp' => 'heroicon-o-musical-note',
                'soundcloud' => 'heroicon-o-musical-note',
                'tiktok' => 'heroicon-o-play',
                default => 'heroicon-o-link',
              };
              return "<a href='{$link['url']}' target='_blank' class='inline-flex items-center gap-1'><x-filament::icon icon='{$icon}' class='size-4' /> {$link['platform']}</a>";
            })->join(' ');
            return new \Illuminate\Support\HtmlString($links);
          })
          ->html()
          ->searchable(),
      ])
      ->filters([
        //
      ])
      ->headerActions([
        Tables\Actions\CreateAction::make()
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
