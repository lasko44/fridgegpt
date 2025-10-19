<script setup lang="ts">
import NumberSelect from '@/shared/NumberSelect.vue';
import IngredientVariation from '@/shared/Variation/IngredientVariation.vue';
import Portion from '@/shared/Variation/Portion.vue';
import Restrictions from '@/shared/Variation/Restrictions.vue';
import { useForm } from '@inertiajs/vue3';
import KitchenStaples from '@/shared/Variation/KitchenStaples.vue';
import { inject } from 'vue';

type Ingredient = { name: string };
interface Recipe {
    id: number;
    name: string;
    description: string;
    ingredients: Ingredient[];
    created_at: string;
}

const props = defineProps<{
    ingredients: Ingredient[];
    recipe_description: string;
}>();

const recipe = inject<Recipe | undefined>('recipe');

const form = useForm({
    recipe_id: recipe?.id ?? null,
    restrictions: [] as string[],
    ingredients: [...props.ingredients],
    portion: "Single",
    servings: 1,
    kitchenStaples: false,
    recipe_description: props.recipe_description,
});

function submit() {
    form.post(route('variation.store'));
}
</script>

<template>
    <div class="my-10 rounded bg-gray-50/50 shadow">
        <h2 class="rounded-t bg-blue-700 p-3 text-xl font-bold text-white">Modify Recipe</h2>

        <div class="p-6 space-y-6">
            <!-- Ingredient editor: full width -->
            <div>
                <IngredientVariation :ingredients="ingredients" v-model="form.ingredients" />
            </div>

            <!-- Controls: stacked on mobile, grid on md+ -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4 md:items-start">
                <!-- Restrictions (takes 2 cols on md if needed) -->
                <div class="w-full md:col-span-2">
                    <Restrictions v-model="form.restrictions" />
                </div>

                <!-- Portion -->
                <div class="w-full">
                    <div class="flex items-center justify-between">
                        <label class="sr-only">Portion</label>
                        <Portion v-model="form.portion" />
                    </div>
                </div>

                <!-- Number of servings -->
                <div class="w-full">
                    <NumberSelect v-model="form.servings" label="Number of Servings" />
                </div>

                <!-- Kitchen staples: place under controls on mobile, right on larger screens -->
                <div class="w-full md:col-span-4 md:flex md:justify-end md:space-x-4">
                    <div class="w-full md:w-auto">
                        <KitchenStaples v-model="form.kitchenStaples" />
                    </div>
                </div>
            </div>

            <!-- Submit: full width on mobile, right-aligned on md+ -->
            <div class="flex flex-col-reverse gap-3 md:flex-row md:justify-end md:items-center">
                <div class="text-sm text-gray-500 md:mr-4">Recipe description saved with submission</div>
                <button
                    type="submit"
                    @click="submit"
                    class="mt-2 md:mt-0 w-full md:w-auto justify-center rounded bg-blue-700 px-6 py-2 text-white transition hover:bg-blue-800 disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="form.processing"
                >
                    Submit
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* small tweaks for tighter mobile spacing */
@media (max-width: 767px) {
    .md\:col-span-2 { grid-column: auto / span 1; }
}
</style>
