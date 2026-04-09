<?php

namespace App\Jobs;

use App\Facades\ModelSlugger;
use App\Models\Recipe;
use App\Models\User;
use App\Services\EmbeddingService;
use App\Services\VariationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateVariationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public function __construct(
        private array $data,
        private int $userId,
    ) {}

    public function handle(): void
    {
        $user = User::findOrFail($this->userId);

        // Resolve slug to id for the service
        if (isset($this->data['recipe_slug']) && !isset($this->data['recipe_id'])) {
            $recipe = Recipe::where('slug', $this->data['recipe_slug'])->firstOrFail();
            $this->data['recipe_id'] = $recipe->id;
        }

        try {
            $service = new VariationService();
            $variation = $service->generate($this->data);
            $variation->store($user);

            Log::info('Variation generated successfully', [
                'user_id' => $this->userId,
                'recipe_id' => $this->data['recipe_id'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Variation generation job failed', [
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
