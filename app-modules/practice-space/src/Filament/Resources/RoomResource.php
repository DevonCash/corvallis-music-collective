<?php

namespace CorvMC\PracticeSpace\Filament\Resources;

use CorvMC\PracticeSpace\Filament\Resources\RoomResource\Pages;
use CorvMC\PracticeSpace\Filament\Resources\RoomResource\RelationManagers;
use CorvMC\PracticeSpace\Models\Room;
use CorvMC\PracticeSpace\Models\RoomCategory;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class RoomResource extends Resource
{
    protected static ?string $model = Room::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Practice Space';


    public static function canAccess(): bool
    {
        /** @var User $user */
        $user = Auth::user();
        return $user->can('manage', Room::class);
    }
   
    public static function getRelations(): array
    {
        return [
            RelationManagers\EquipmentRelationManager::class,
            RelationManagers\MaintenanceSchedulesRelationManager::class,
            RelationManagers\BookingsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRooms::route('/'),
            'create' => Pages\CreateRoom::route('/create'),
            'edit' => Pages\EditRoom::route('/{record}/edit'),
        ];
    }
    
    public static function getFormActionsAlignment(): string
    {
        return 'right';
    }
    
    public static function getFormActions(): array
    {
        return [
            Forms\Components\Actions\Action::make('save')
                ->label('Save changes')
                ->submit('save')
                ->color('primary'),
                
            Forms\Components\Actions\Action::make('cancel')
                ->label('Cancel')
                ->url(fn () => static::getUrl('index'))
                ->color('gray'),
                
            Forms\Components\Actions\Action::make('delete')
                ->label('Delete room')
                ->action(fn ($record) => $record->delete())
                ->requiresConfirmation()
                ->modalHeading('Delete Room')
                ->modalDescription('Are you sure you want to delete this room? This action cannot be undone.')
                ->modalSubmitActionLabel('Yes, delete room')
                ->color('danger')
                ->visible(fn ($record) => $record && $record->exists),
        ];
    }
}
