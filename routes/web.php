<?php

use App\Http\Controllers\AuthController;
use App\Models\Post;
use Illuminate\Support\Facades\Route;

// Page::all()->each(function (Page $page) {
//     Route::get($page->route, function () use ($page) {
//         return Blade::render(
//             <<<BLADE
// @extends('layouts.app')
// @section('content')
// $page->template
// @endsection
// BLADE
//             ,
//             ["title" => $page->name, ...$page->localization["en"]]
//         );
//     });
// });

Route::get("/", function () {
    $events = \App\Models\Event::published()->limit(3)->get();

    $posts = \App\Models\Post::whereDate("published_at", "<", now())
        ->orderBy("published_at", "desc")
        ->limit(3)
        ->get();

    // $events = \App\Models\Event::all();
    return view("welcome", [
        "events" => $events,
        "posts" => $posts,
    ]);
});

Route::get("/news", function () {
    $posts = Post::published()->orderBy("published_at", "desc")->paginate(10);
    return view("pages.post.index", [
        "posts" => $posts,
    ]);
});

Route::get("/news/{post}", function () {
    return view("pages.post.show", [
        "post" => Post::findOrFail(request("post")),
    ]);
});

Route::get("/events", function () {
    $events = \App\Models\Event::published()->paginate(10);
    return view("pages.event.index", [
        "events" => $events,
    ]);
});

Route::group(["middleware" => ["guest"]], function () {
    Route::get("/login", [AuthController::class, "showLogin"])->name(
        "login.show"
    );
    Route::post("/login", [AuthController::class, "login"])->name("login");
    Route::get("/logout", [AuthController::class, "logout"])->name("logout");
    Route::get("verify-login/{token}", [
        AuthController::class,
        "verifyLogin",
    ])->name("verify-login");
});
