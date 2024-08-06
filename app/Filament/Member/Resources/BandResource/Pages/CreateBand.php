<?php

namespace App\Filament\Member\Resources\BandResource\Pages;

use App\Filament\Member\Resources\BandResource;
use Filament\Actions;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Pages\CreateRecord;

class CreateBand extends CreateRecord
{
    protected static string $resource = BandResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $newBand = static::getModel()::create($data);
        $newBand->members()->attach(auth()->user(), ['role' => 'owner']);
        return $newBand;
    }
}
