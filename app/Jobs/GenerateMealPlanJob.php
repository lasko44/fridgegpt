<?php

namespace App\Jobs;

use App\Models\MealPlan;
use App\Services\KrogerService;
use App\Services\MealPlanService;
use App\Services\PushNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Generates a meal plan via GPT in the background.
 */
class GenerateMealPlanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 300;

    public int $backoff = 10;

    public function __construct(
        public MealPlan $mealPlan,
    ) {}

    public function handle(MealPlanService $service, PushNotificationService $push): void
    {
        $service->generatePlan($this->mealPlan);

        $plan = $this->mealPlan->fresh();

        if ($plan && $plan->status === 'complete') {
            $push->sendToUser(
                $plan->user,
                'Meal plan ready!',
                "{$plan->name} is all set with " . $plan->slots()->count() . ' recipes.',
                ['type' => 'meal_plan', 'uuid' => $plan->uuid],
            );
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateMealPlanJob failed', [
            'meal_plan_id' => $this->mealPlan->id,
            'error' => $exception->getMessage(),
        ]);

        $this->mealPlan->update(['status' => 'failed']);

        // Notify the user even on failure
        $push = app(PushNotificationService::class);
        $push->sendToUser(
            $this->mealPlan->user,
            'Meal plan failed',
            'Something went wrong generating your meal plan. Please try again.',
            ['type' => 'meal_plan_failed', 'uuid' => $this->mealPlan->uuid],
        );
    }
}
