<?php

namespace Tests\Feature;

use App\Models\MealPlan;
use App\Models\User;
use Tests\TestCase;

class MealPlanShowTest extends TestCase
{
    public function test_meal_plan_show_renders_without_502(): void
    {
        $user = User::where('email', 'premium@fridgegpt.test')->first();
        $this->assertNotNull($user, 'Premium test user not found');

        $plan = MealPlan::where('user_id', $user->id)
            ->where('status', 'complete')
            ->first();

        if (! $plan) {
            $this->markTestSkipped('No complete meal plan exists');
        }

        $response = $this->actingAs($user)->get("/meal-plans/{$plan->uuid}");

        $this->assertNotEquals(502, $response->status());
        $this->assertNotEquals(500, $response->status());
        $this->assertEquals(200, $response->status());
    }

    public function test_all_main_pages_render(): void
    {
        $user = User::where('email', 'premium@fridgegpt.test')->first();

        $pages = ['/', '/recipes', '/tokens', '/create', '/meal-plans', '/meal-plans/create'];

        foreach ($pages as $path) {
            $response = $this->actingAs($user)->get($path);
            $this->assertNotEquals(502, $response->status(), "Page {$path} returned 502");
            $this->assertNotEquals(500, $response->status(), "Page {$path} returned 500");
        }
    }
}
