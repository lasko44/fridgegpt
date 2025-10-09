<?php

namespace App\Http\Controllers;

use App\Facades\Variation;
use App\Http\Requests\VariationRequest;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
    public function store(VariationRequest $request)
    {
        try {
            $variation = Variation::generate($request->validated());

            $recipe = $variation->store(Auth::user());

           // Redirect to the newly created recipe's show page using the slug

           return redirect()->route('recipe.show', $recipe->slug)
               ->with('success', 'Variation generated successfully!');

        } catch (Exception $e) {
            return back()->withErrors([
                'error' => 'An error occurred while generating variation: ' . $e->getMessage()
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
