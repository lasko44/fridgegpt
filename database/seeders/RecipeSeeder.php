<?php

namespace Database\Seeders;

use App\Models\Recipe;
use App\Models\User;
use App\Services\EmbeddingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RecipeSeeder extends Seeder
{
    public function run(): void
    {
        $embeddingService = new EmbeddingService();

        // Create a system user for seeded recipes
        $user = User::firstOrCreate(
            ['email' => 'system@fridgegpt.test'],
            [
                'name' => 'FridgeGPT',
                'username' => 'fridgegpt',
                'password' => bcrypt(Str::random(32)),
            ]
        );

        $recipes = [
            [
                'name' => 'Garlic Butter Chicken with Rice',
                'input_ingredients' => 'chicken, rice, garlic, butter, onion',
                'ingredients' => ['Chicken breast', 'Rice', 'Garlic', 'Butter', 'Onion', 'Salt', 'Pepper', 'Chicken broth'],
                'description' => "Title: Garlic Butter Chicken with Rice\n\nIngredients:\n- 2 chicken breasts\n- 1 cup rice\n- 4 cloves garlic, minced\n- 3 tbsp butter\n- 1 onion, diced\n- 2 cups chicken broth\n- Salt and pepper to taste\n\nInstructions:\n1. Season chicken with salt and pepper. In a skillet, melt 2 tbsp butter over medium-high heat. Cook chicken 6-7 minutes per side until golden. Set aside.\n2. In the same pan, add remaining butter. Sauté onion until soft, then add garlic and cook 1 minute until fragrant.\n3. Add rice and toast for 2 minutes. Pour in chicken broth, bring to a boil, reduce heat, cover and simmer 18 minutes.\n4. Slice chicken, serve over rice. Spoon pan juices on top.",
            ],
            [
                'name' => 'Classic Pasta Carbonara',
                'input_ingredients' => 'pasta, eggs, bacon, parmesan, garlic',
                'ingredients' => ['Spaghetti', 'Eggs', 'Bacon', 'Parmesan cheese', 'Garlic', 'Black pepper'],
                'description' => "Title: Classic Pasta Carbonara\n\nIngredients:\n- 400g spaghetti\n- 4 eggs\n- 200g bacon, diced\n- 1 cup parmesan, grated\n- 3 cloves garlic\n- Black pepper\n\nInstructions:\n1. Cook spaghetti in salted boiling water until al dente. Reserve 1 cup pasta water before draining.\n2. While pasta cooks, fry bacon in a skillet until crispy. Add garlic for the last minute.\n3. Whisk eggs and parmesan together in a bowl.\n4. Toss hot drained pasta into the bacon skillet (off heat). Pour egg mixture over and toss rapidly, adding pasta water as needed for a creamy sauce.\n5. Season with black pepper and extra parmesan.",
            ],
            [
                'name' => 'Vegetable Stir Fry with Tofu',
                'input_ingredients' => 'tofu, broccoli, bell pepper, soy sauce, ginger',
                'ingredients' => ['Firm tofu', 'Broccoli', 'Bell pepper', 'Soy sauce', 'Ginger', 'Sesame oil', 'Rice'],
                'description' => "Title: Vegetable Stir Fry with Tofu\n\nIngredients:\n- 1 block firm tofu, cubed\n- 2 cups broccoli florets\n- 1 bell pepper, sliced\n- 3 tbsp soy sauce\n- 1 tbsp fresh ginger, grated\n- 1 tbsp sesame oil\n- Cooked rice for serving\n\nInstructions:\n1. Press tofu for 15 minutes, then cube. Heat sesame oil in a wok over high heat.\n2. Fry tofu cubes until golden on all sides, about 5 minutes. Remove and set aside.\n3. In the same wok, stir-fry broccoli and bell pepper for 3-4 minutes until crisp-tender.\n4. Add ginger, cook 30 seconds. Return tofu to wok, add soy sauce, toss to coat.\n5. Serve over steamed rice.",
            ],
            [
                'name' => 'Salmon with Lemon and Asparagus',
                'input_ingredients' => 'salmon, lemon, asparagus, olive oil, garlic',
                'ingredients' => ['Salmon fillets', 'Lemon', 'Asparagus', 'Olive oil', 'Garlic', 'Salt', 'Pepper'],
                'description' => "Title: Salmon with Lemon and Asparagus\n\nIngredients:\n- 2 salmon fillets\n- 1 lemon\n- 1 bunch asparagus, trimmed\n- 2 tbsp olive oil\n- 3 cloves garlic, minced\n- Salt and pepper\n\nInstructions:\n1. Preheat oven to 200°C (400°F). Line a baking sheet with parchment paper.\n2. Arrange salmon and asparagus on the sheet. Drizzle with olive oil, sprinkle garlic, salt, and pepper.\n3. Squeeze half the lemon over everything. Slice remaining half and place on the salmon.\n4. Bake 12-15 minutes until salmon flakes easily and asparagus is tender.",
            ],
            [
                'name' => 'Beef Tacos with Fresh Salsa',
                'input_ingredients' => 'beef, tortillas, tomato, onion, lime, cilantro',
                'ingredients' => ['Ground beef', 'Tortillas', 'Tomato', 'Onion', 'Lime', 'Cilantro', 'Cumin', 'Chili powder'],
                'description' => "Title: Beef Tacos with Fresh Salsa\n\nIngredients:\n- 500g ground beef\n- 8 small tortillas\n- 2 tomatoes, diced\n- 1 onion, diced\n- 2 limes\n- Fresh cilantro\n- 1 tsp cumin\n- 1 tsp chili powder\n\nInstructions:\n1. Make fresh salsa: combine diced tomato, half the onion, chopped cilantro, juice of 1 lime, and a pinch of salt.\n2. Brown ground beef with remaining onion. Add cumin and chili powder, cook 5 minutes.\n3. Warm tortillas in a dry skillet or microwave.\n4. Fill tortillas with seasoned beef, top with fresh salsa, and squeeze lime over each taco.",
            ],
            [
                'name' => 'Mushroom Risotto',
                'input_ingredients' => 'rice, mushrooms, onion, parmesan, butter, white wine',
                'ingredients' => ['Arborio rice', 'Mushrooms', 'Onion', 'Parmesan', 'Butter', 'White wine', 'Vegetable broth'],
                'description' => "Title: Mushroom Risotto\n\nIngredients:\n- 1.5 cups arborio rice\n- 300g mixed mushrooms, sliced\n- 1 onion, finely diced\n- 1/2 cup parmesan, grated\n- 3 tbsp butter\n- 1/2 cup white wine\n- 4 cups vegetable broth, warm\n\nInstructions:\n1. Sauté mushrooms in 1 tbsp butter until golden. Set aside.\n2. In the same pot, melt 1 tbsp butter. Cook onion until soft. Add rice, stir 2 minutes.\n3. Pour in wine, stir until absorbed. Add broth one ladle at a time, stirring frequently, waiting for each to absorb before adding the next (about 18-20 minutes total).\n4. Stir in mushrooms, remaining butter, and parmesan. Season with salt and pepper. Serve immediately.",
            ],
            [
                'name' => 'Greek Salad with Grilled Chicken',
                'input_ingredients' => 'chicken, cucumber, tomato, feta, olives, olive oil',
                'ingredients' => ['Chicken breast', 'Cucumber', 'Tomato', 'Feta cheese', 'Kalamata olives', 'Olive oil', 'Red onion', 'Oregano'],
                'description' => "Title: Greek Salad with Grilled Chicken\n\nIngredients:\n- 2 chicken breasts\n- 1 cucumber, chopped\n- 2 tomatoes, chopped\n- 100g feta, crumbled\n- 1/2 cup kalamata olives\n- 3 tbsp olive oil\n- 1/2 red onion, sliced\n- 1 tsp dried oregano\n\nInstructions:\n1. Season chicken with oregano, salt, pepper, and 1 tbsp olive oil. Grill or pan-fry 6-7 minutes per side.\n2. While chicken rests, combine cucumber, tomato, red onion, and olives in a bowl.\n3. Drizzle with remaining olive oil and a squeeze of lemon. Toss gently.\n4. Slice chicken and arrange over salad. Top with crumbled feta.",
            ],
            [
                'name' => 'Creamy Tomato Soup with Grilled Cheese',
                'input_ingredients' => 'tomato, cream, bread, cheese, butter, basil',
                'ingredients' => ['Canned tomatoes', 'Heavy cream', 'Bread', 'Cheddar cheese', 'Butter', 'Fresh basil', 'Onion', 'Garlic'],
                'description' => "Title: Creamy Tomato Soup with Grilled Cheese\n\nIngredients:\n- 2 cans crushed tomatoes\n- 1/2 cup heavy cream\n- 4 slices bread\n- Cheddar cheese, sliced\n- 3 tbsp butter\n- Fresh basil\n- 1 onion, diced\n- 2 cloves garlic\n\nInstructions:\n1. Melt 1 tbsp butter in a pot. Sauté onion until soft, add garlic for 1 minute.\n2. Add crushed tomatoes, bring to a simmer. Cook 15 minutes. Blend until smooth with an immersion blender.\n3. Stir in heavy cream and torn basil leaves. Season with salt and pepper.\n4. For grilled cheese: butter bread slices, layer cheese between two slices. Cook in a skillet over medium heat until golden on both sides.\n5. Serve soup in bowls with grilled cheese on the side.",
            ],
            [
                'name' => 'Shrimp Scampi Pasta',
                'input_ingredients' => 'shrimp, pasta, garlic, butter, lemon, white wine',
                'ingredients' => ['Shrimp', 'Linguine', 'Garlic', 'Butter', 'Lemon', 'White wine', 'Red pepper flakes', 'Parsley'],
                'description' => "Title: Shrimp Scampi Pasta\n\nIngredients:\n- 500g shrimp, peeled and deveined\n- 400g linguine\n- 6 cloves garlic, minced\n- 4 tbsp butter\n- 1 lemon\n- 1/2 cup white wine\n- Pinch of red pepper flakes\n- Fresh parsley\n\nInstructions:\n1. Cook linguine in salted boiling water until al dente. Reserve 1 cup pasta water.\n2. In a large skillet, melt butter over medium heat. Add garlic and red pepper flakes, cook 1 minute.\n3. Add shrimp, cook 2 minutes per side until pink. Remove shrimp.\n4. Pour in white wine and lemon juice. Simmer 2 minutes.\n5. Toss in pasta, return shrimp, add pasta water as needed. Garnish with parsley and lemon zest.",
            ],
            [
                'name' => 'Potato and Cheese Omelette',
                'input_ingredients' => 'eggs, potato, cheese, onion, butter',
                'ingredients' => ['Eggs', 'Potato', 'Cheddar cheese', 'Onion', 'Butter', 'Salt', 'Pepper', 'Chives'],
                'description' => "Title: Potato and Cheese Omelette\n\nIngredients:\n- 3 eggs\n- 1 medium potato, diced small\n- 1/2 cup cheddar, shredded\n- 1/4 onion, diced\n- 1 tbsp butter\n- Salt and pepper\n- Fresh chives\n\nInstructions:\n1. Boil diced potato for 5 minutes until just tender. Drain.\n2. Melt butter in a non-stick skillet. Sauté onion and potato until golden, about 4 minutes.\n3. Beat eggs with salt and pepper. Pour over the potatoes.\n4. Cook on low heat until edges set. Sprinkle cheese on one half.\n5. Fold omelette in half, cook 1 more minute. Slide onto plate and top with chives.",
            ],
        ];

        $this->command->info('Seeding ' . count($recipes) . ' recipes with embeddings...');

        foreach ($recipes as $recipeData) {
            $slug = Str::slug($recipeData['name']);

            // Skip if already exists
            if (Recipe::where('slug', $slug)->exists()) {
                $this->command->info("  Skipping: {$recipeData['name']} (exists)");
                continue;
            }

            $recipe = $user->recipe()->create([
                'name' => $recipeData['name'],
                'slug' => $slug,
                'description' => $recipeData['description'],
                'input_ingredients' => $recipeData['input_ingredients'],
                'image_url' => '/images/fridge_meal.png',
            ]);

            // Create ingredient records
            $recipe->ingredients()->createMany(
                array_map(fn($name) => ['name' => $name], $recipeData['ingredients'])
            );

            // Generate and store embedding
            try {
                $embeddingText = "Recipe: {$recipeData['name']}. Ingredients: {$recipeData['input_ingredients']}. {$recipeData['description']}";
                $embedding = $embeddingService->embed($embeddingText);
                $recipe->setEmbedding($embedding);
                $this->command->info("  ✓ {$recipeData['name']}");
            } catch (\Exception $e) {
                $this->command->warn("  ✗ {$recipeData['name']} - embedding failed: {$e->getMessage()}");
            }
        }

        $this->command->info('Done!');
    }
}
