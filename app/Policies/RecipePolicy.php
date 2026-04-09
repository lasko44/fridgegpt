<?php

namespace App\Policies;

use App\Models\Recipe;
use App\Models\User;

/**
 * Policy for authorizing recipe-related actions.
 */
class RecipePolicy
{
    /**
     * Determine whether the user can view any recipes.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the recipe.
     */
    public function view(?User $user, Recipe $recipe): bool
    {
        // Public recipes can be viewed by anyone
        // Private recipes can only be viewed by the owner
        return $recipe->user_id === null || $user?->id === $recipe->user_id;
    }

    /**
     * Determine whether the user can create recipes.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the recipe.
     */
    public function update(User $user, Recipe $recipe): bool
    {
        return $user->id === $recipe->user_id;
    }

    /**
     * Determine whether the user can delete the recipe.
     */
    public function delete(User $user, Recipe $recipe): bool
    {
        return $user->id === $recipe->user_id;
    }

    /**
     * Determine whether the user can restore the recipe.
     */
    public function restore(User $user, Recipe $recipe): bool
    {
        return $user->id === $recipe->user_id;
    }

    /**
     * Determine whether the user can permanently delete the recipe.
     */
    public function forceDelete(User $user, Recipe $recipe): bool
    {
        return $user->id === $recipe->user_id;
    }

    /**
     * Determine whether the user can create variations of the recipe.
     */
    public function createVariation(User $user, Recipe $recipe): bool
    {
        return $user->is_subscribed && $user->id === $recipe->user_id;
    }
}
