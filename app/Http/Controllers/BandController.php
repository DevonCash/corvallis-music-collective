<?php
namespace App\Http\Controllers;
use App\Models\Band;
use Illuminate\Support\Facades\Route;

class BandsController
{
    function list()
    {
        return inertia("Admin/Band/ListBands", [
            "bands" => Band::paginate(24),
        ]);
    }

    function create()
    {
        return inertia("Admin/Band/CreateBand");
    }

    function edit(Band $band)
    {
        ray($band);
        return inertia("Admin/Band/EditBand", ["band" => $band]);
    }

    static function routes()
    {
        Route::controller(self::class)->group(function () {
            Route::get("bands/{band}/edit", "edit");
            Route::get("bands/create", "create");
            Route::get("bands/", "list");
        });
    }
}
