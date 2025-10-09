// Recipe interface implementation
export interface Ingredient {
    name: string;
    quantity: string;
}

export interface Recipe {
    name: string;
    description: string;
    ingredients: Ingredient[];
    created_at: string;
}
