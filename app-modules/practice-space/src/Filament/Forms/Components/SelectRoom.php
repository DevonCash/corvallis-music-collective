<?php

namespace CorvMC\PracticeSpace\Filament\Forms\Components;

use Filament\Forms\Components\Select;
use CorvMC\PracticeSpace\Models\Room;
use Illuminate\Support\Facades\Blade;

class SelectRoom {
  public static function make(string $name = 'room_id'): Select
  {
    return  Select::make($name)
    ->label('Room')
    ->visible(fn() => Room::count() > 1)
    ->allowHtml()
    ->searchable(['name', 'description'])
    ->default(function () {
        return Room::first()->id;
    })->options(function () {
        // Get all active rooms
        return Room::where('is_active', true)
            ->get()
            ->mapWithKeys(function ($room) {
                // Format price - show without cents if it's a whole dollar amount
                $hourlyRate = $room->hourly_rate;
                $formattedPrice = floor($hourlyRate) == $hourlyRate
                    ? '$' . number_format($hourlyRate, 0)
                    : '$' . number_format($hourlyRate, 2);
                
                // Create a formatted label with HTML
                $label = Blade::render("
                    <div class='flex flex-col py-1'>
                        <div class='text-sm flex items-center gap-2'>
                            <span class='font-medium text-gray-900 dark:text-gray-100'>{$room->name}</span>
                            <span class='text-gray-500'>{$formattedPrice}/hr</span>
                            <span class='flex items-center text-gray-500'>
                                <x-filament::icon icon='heroicon-o-users' class='w-4 h-4 text-gray-400 mr-1' />
                                {$room->capacity}
                            </span>
                        </div>
                        " . ($room->description ? "<div class='text-xs text-gray-400 mt-1 truncate max-w-md'>{$room->description}</div>" : "") . "
                    </div>
                ");
                
                return [$room->id => $label];
            })
            ->toArray();
    });
    
  }
}