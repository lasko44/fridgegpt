<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource for serializing Recipe model data.
 */
class RecipeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'image_url' => $this->image_url,
            'is_variation' => $this->is_variation,
            'ingredients' => IngredientResource::collection($this->whenLoaded('ingredients')),
            'variations' => RecipeResource::collection($this->whenLoaded('variations')),
            'created_at' => $this->resource->getAttributes()['created_at'] ?? null,
            'updated_at' => $this->resource->getAttributes()['updated_at'] ?? null,
        ];
    }
}
