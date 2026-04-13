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
            'servings' => $this->servings,
            'calories_per_serving' => $this->calories_per_serving,
            'protein_per_serving' => $this->protein_per_serving,
            'carbs_per_serving' => $this->carbs_per_serving,
            'fat_per_serving' => $this->fat_per_serving,
            'nutrition_source' => $this->nutrition_source,
            'ingredients' => IngredientResource::collection($this->whenLoaded('ingredients')),
            'variations' => RecipeResource::collection($this->whenLoaded('variations')),
            'created_at' => $this->resource->getAttributes()['created_at'] ?? null,
            'updated_at' => $this->resource->getAttributes()['updated_at'] ?? null,
        ];
    }
}
