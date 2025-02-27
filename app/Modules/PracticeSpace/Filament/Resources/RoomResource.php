<?php

namespace App\Modules\PracticeSpace\Filament\Resources;

use App\Modules\PracticeSpace\Filament\Resources\RoomResource\Pages;
use App\Modules\PracticeSpace\Filament\Resources\RoomResource\RelationManagers;
use App\Modules\PracticeSpace\Models\Room;
use Filament\Resources\Resource;

class RoomResource extends Resource
{
    protected static ?string $model = Room::class;

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRooms::route('/'),
            'view' => Pages\ViewRoom::route('/{record}'),
        ];
    }
}
