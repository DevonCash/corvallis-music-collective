<?php

namespace App\Filament\Member\Resources\EventResource\Pages;

use App\Filament\Member\Resources\EventResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateEvent extends CreateRecord
{
    protected static string $resource = EventResource::class;
}
