<?php
namespace App\Http\Controllers;

class DashboardController
{
    public function show()
    {
        return inertia("Admin/Dashboard");
    }
}
