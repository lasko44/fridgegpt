<?php

namespace App\Http\Controllers;

use App\Facades\Variation;
use App\Http\Requests\VariationRequest;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class VariationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(VariationRequest $request): \Symfony\Component\HttpFoundation\Response
    {
        try {
            $variation = Variation::generate($request->validated());
            $recipe = $variation->store(Auth::user());

            session()->flash('flash', [
                'success' => 'Variation generated successfully!'
            ]);

            return Inertia::location(route('recipe.show', $recipe->slug));
        } catch (Exception $e) {
            return back()->with([
                'flash' => [
                    'error' => 'An error occurred while generating variation: ' . $e->getMessage()
                ]
            ]);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
