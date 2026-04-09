<?php

namespace App\Http\Controllers;

use App\Http\Requests\VariationRequest;
use App\Models\Recipe;
use App\Services\TokenService;
use App\Services\VariationService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class VariationController extends Controller
{
    public function store(VariationRequest $request, TokenService $tokenService): RedirectResponse
    {
        $user = Auth::user();
        $data = $request->validated();

        // Check tokens first
        if (!$tokenService->hasTokensFor($user, 'variation')) {
            return back()
                ->withErrors(['error' => 'You\'re out of tokens! Buy more to create variations.'])
                ->with('flash', ['error' => 'Insufficient tokens.']);
        }

        // Resolve slug to id
        if (isset($data['recipe_slug'])) {
            $recipe = Recipe::where('slug', $data['recipe_slug'])->firstOrFail();
            $data['recipe_id'] = $recipe->id;
        }

        try {
            $service = new VariationService();
            $variation = $service->generate($data);
            $newRecipe = $variation->store($user);

            // Spend token after successful generation
            $tokenService->spend($user, 'variation', [
                'recipe_id' => $newRecipe->id,
                'parent_recipe_id' => $data['recipe_id'] ?? null,
            ]);

            return redirect()->route('recipe.show', $newRecipe->slug)
                ->with('flash', ['success' => 'Variation created!']);
        } catch (Exception $e) {
            Log::error('Variation failed', ['error' => $e->getMessage()]);
            return back()
                ->withErrors(['error' => $e->getMessage()])
                ->with('flash', ['error' => 'Failed to generate variation: ' . $e->getMessage()]);
        }
    }
}
