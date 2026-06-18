<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user()->load('cvProfile');

        return view('dashboard.index', [
            'user' => $user,
            'profile' => $user->cvProfile,
        ]);
    }
}
