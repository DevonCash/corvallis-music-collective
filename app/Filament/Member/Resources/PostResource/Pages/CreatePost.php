<?php

namespace App\Filament\Member\Resources\PostResource\Pages;

use App\Filament\Member\Resources\PostResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;
}
