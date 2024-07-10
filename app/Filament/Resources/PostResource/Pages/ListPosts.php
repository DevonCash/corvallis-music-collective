<?php

namespace App\Filament\Resources\PostResource\Pages;

use App\Filament\Resources\PostResource;
use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Models\Post;

class ListPosts extends ListRecords
{
    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make("newPost")
                ->label("New Post")
                ->action(function () {
                    $post = Post::create([
                        "title" => "New Post",
                    ]);

                    return redirect(
                        PostResource::getUrl("edit", ["record" => $post])
                    );
                }),
        ];
    }
}
