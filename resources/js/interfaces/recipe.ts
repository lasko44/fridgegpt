// Recipe interface implementation
export interface Ingredient {
    name: string;
    quantity: string;
    amount?: number;
    unit?: string;
    calories?: number;
    protein?: number;
    carbs?: number;
    fat?: number;
}

export interface Recipe {
    id?: number;
    name: string;
    description: string;
    ingredients: Ingredient[];
    image_url: string;
    slug: string;
    created_at: string;
    is_variation?: boolean;
    parent_recipe?: { name: string; slug: string } | null;
    calories_per_serving?: number | null;
    protein_per_serving?: number | null;
    carbs_per_serving?: number | null;
    fat_per_serving?: number | null;
    nutrition_source?: string | null;
    servings?: number | null;
}
