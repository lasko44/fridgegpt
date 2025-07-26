<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        dd($request->ip());
        auth()->user();
        return Inertia::render('Home', [
            'recipe' => session('recipe'),
            'recipes' => session('recipes', []),
        ]);
    }
}
