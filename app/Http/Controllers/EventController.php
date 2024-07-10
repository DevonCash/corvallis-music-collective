<?php
namespace App\Http\Controllers;
use App\Models\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Carbon\Carbon;

class EventController
{
    function list(Request $request)
    {
        $month = Carbon::parse($request->input("month", now()));
        $month->startOfMonth();
        $nextMonth = new Carbon($month);
        $nextMonth->addMonth()->startOfMonth();
        ray($month);
        return inertia("Public/Event/ListEvents", [
            "month" => $month,
            "events" => Event::published()
                ->with("bands:id,name")
                ->with("venue:id,name")
                ->where("start_time", ">=", $month)
                ->where("start_time", "<", $nextMonth)
                ->orderBy("start_time")
                ->get(),
        ]);
    }

    function show(Event $event)
    {
        return inertia("Public/Event/ShowEvent", [
            "event" => $event,
            "bands" => $event->bands,
            "venue" => $event->venue,
        ]);
    }

    static function routes()
    {
        Route::controller(self::class)->group(function () {
            Route::get("events/", "list")->name("events.list");
            Route::get("events/{event}", "show")->name("events.show");
        });
    }
}
