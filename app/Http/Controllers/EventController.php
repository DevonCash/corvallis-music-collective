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
        $tag = $request->input("tag");
        $month = Carbon::parse($request->input("month", now()));
        $month->startOfMonth();
        $nextMonth = new Carbon($month);
        $nextMonth->addMonth()->startOfMonth();

        $events = Event::published()
            ->with('poster')
            ->with("bands:id,name")
            ->with("venue:id,name,link")
            ->where("start_time", ">=", $month)
            ->where("start_time", "<", $nextMonth)
            ->orderBy("start_time");

        if ($tag) {
            $events->whereJsonContains("tags", $tag);
        }

        return inertia("Public/Event/ListEvents", [
            "month" => $month,
            "events" => $events->get(),
            "tag" => $tag,
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

    function create()
    {
        return inertia("Public/Event/CreateEvent", ["csrf" => csrf_token()]);
    }

    function handleSubmit()
    {
        ray(request()->all());

        $data = request()->validate([
            "name" => "required",
            "description" => "string",
            "start_date" => "required|date_format:Y-m-d",
            "start_time" => "required|date_format:H:i",
            "tags" => "array",
            "tags.*" => "string",
        ]);

        $event = Event::create([
            "name" => $data["name"],
            "description" => $data["description"],
            "start_time" => Carbon::parse(
                $data["start_date"] . " " . $data["start_time"]
            ),
            "tags" => $data["tags"] ?? [],
        ]);
        // if (request()->hasFile("poster")) {
        //     $event
        //         ->addMediaFromRequest("poster")
        //         ->toMediaCollection("posters", "s3");
        // }

        return response()->json([
            "message" => "Event submitted! Keep an eye out for it on the site!",
        ]);
    }

    static function routes()
    {
        Route::controller(self::class)->group(function () {
            Route::post("events", "handleSubmit")->name("events.submit");
            Route::get("events", "list")->name("events.list");
            Route::get("events/submit", "create")->name("events.create");
            Route::get("events/{event}", "show")->name("events.show");
        });
    }
}
