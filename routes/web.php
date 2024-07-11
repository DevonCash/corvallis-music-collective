<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers;
use Inertia\Inertia;
use App\Models\Event;
use App\Models\Post;

Route::get("/", function () {
    $upcomingEvents = Event::published()
        ->where("start_time", ">=", now())
        ->whereJsonContains("tags", "CMC")
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

Route::get("donate", fn() => redirect("contribute/donate"));

Route::get("contribute/donate", function () {
    return inertia("Public/Donate");
});

Route::get("contribute/volunteer", function () {
    return inertia("Public/Volunteer");
});

Route::get("contribute", function () {
    return inertia("Public/Contribute");
});

Route::get(
    "events/community-events",
    fn() => inertia("Public/CommunityEventGuidelines")
);

Controllers\PostController::routes();
Controllers\EventController::routes();
