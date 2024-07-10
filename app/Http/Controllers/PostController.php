<?php
namespace App\Http\Controllers;
use App\Models\Post;
use Illuminate\Support\Facades\Route;

class PostController
{
    function list()
    {
        return inertia("Public/Post/ListPosts", [
            "posts" => Post::published()
                ->with("authors:id,name")
                ->orderBy("published_at", "desc")
                ->paginate(10),
        ]);
    }

    function show(Post $post)
    {
        return inertia("Public/Post/ShowPost", [
            "post" => $post,
            "authors" => $post->authors,
        ]);
    }

    static function routes()
    {
        Route::controller(self::class)->group(function () {
            Route::get("posts/", "list")->name("posts.list");
            Route::get("posts/{post}", "show")->name("posts.show");
        });
    }
}
