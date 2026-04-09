<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource for serializing subscription data.
 */
class SubscriptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'is_subscribed' => $this->resource['is_subscribed'],
            'is_trialing' => $this->resource['is_trialing'],
            'trial_ends_at' => $this->resource['trial_ends_at']?->toISOString(),
            'started_at' => $this->resource['started_at']?->toISOString(),
            'current_period_end' => $this->resource['current_period_end']?->toISOString(),
        ];
    }
}
