<?php

namespace App\Policies;

use App\Models\Recipe;
use App\Models\User;

/**
 * Policy for authorizing recipe variation actions.
 */
class VariationPolicy
{
    /**
     * Determine whether the user can view variations.
     */
    public function viewAny(User $user): bool
    {
        return $user->is_subscribed;
    }

    /**
     * Determine whether the user can view a specific variation.
     */
    public function view(User $user, Recipe $variation): bool
    {
        return $user->is_subscribed && $user->id === $variation->user_id;
    }

    /**
     * Determine whether the user can create variations.
     */
    public function create(User $user): bool
    {
        return $user->is_subscribed;
    }

    /**
     * Determine whether the user can create a variation of a specific recipe.
     */
    public function createForRecipe(User $user, Recipe $recipe): bool
    {
        return $user->is_subscribed && $user->id === $recipe->user_id;
    }

    /**
     * Determine whether the user can delete a variation.
     */
    public function delete(User $user, Recipe $variation): bool
    {
        return $user->is_subscribed && $user->id === $variation->user_id;
    }
}
