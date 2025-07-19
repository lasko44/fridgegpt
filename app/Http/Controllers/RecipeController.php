<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use App\Services\RecipeRateLimiter;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

class RecipeController extends Controller
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
     * @throws ConnectionException
     */
   public function store(Request $request): object
   {
       $rateLimiter = new RecipeRateLimiter();

       if ($rateLimiter->tooManyAttempts($request->ip())) {
           $seconds = $rateLimiter->availableIn($request->ip());
           return response()->json([
               'error' => "Too many requests. Please wait {$seconds} seconds before trying again."
           ], Response::HTTP_TOO_MANY_REQUESTS);
       }
       $rateLimiter->hit($request->ip());

       $ingredients = $request->input('ingredients');
       $recipe = app('App\Services\RecipeService')->generateRecipe($ingredients);

       return redirect()->route('home')->with('recipe', $recipe);
   }

    /**
     * Display the specified resource.
     */
    public function show(Recipe $recipe)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Recipe $recipe)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Recipe $recipe)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Recipe $recipe)
    {
        //
    }
}
