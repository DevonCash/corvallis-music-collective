<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers;
use Inertia\Inertia;
use App\Models\Event;
use App\Models\Post;

Route::get("/", function () {
    $upcomingEvents = Event::published()
        ->where("start_time", ">=", now())
        ->orderBy("start_time", "asc")
        ->limit(3)
        ->get();

    $recentPosts = Post::published()
        ->orderBy("published_at", "desc")
        ->limit(3)
        ->get();

    return inertia("Public/Home", [
        "events" => $upcomingEvents,
        "posts" => $recentPosts,
    ]);
});

Route::get("/donate", function () {
    return inertia("Public/Donate");
});

Route::get("/volunteer", function () {
    return inertia("Public/Volunteer");
});

Controllers\PostController::routes();
Controllers\EventController::routes();
