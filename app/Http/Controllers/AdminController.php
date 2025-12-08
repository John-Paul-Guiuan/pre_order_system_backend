<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class AdminController extends Controller
{
    public function dashboard(Request $request)
    {
        // return some data for the dashboard
        return response()->json([
            'stats' => [
                'users' => \App\Models\User::count(),
                'orders' => \App\Models\Order::count(),
                // etc
            ]
        ]);
    }
}