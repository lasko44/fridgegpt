// Recipe interface implementation
export interface Ingredient {
    name: string;
    quantity: string;
}

export interface Recipe {
    name: string;
    description: string;
    ingredients: Ingredient[];
    image_url: string;
    slug: string;
    created_at: string;
}
