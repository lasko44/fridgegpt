<script setup lang="ts">
import NumberSelect from '@/shared/NumberSelect.vue';
import IngredientVariation from '@/shared/Variation/IngredientVariation.vue';
import Portion from '@/shared/Variation/Portion.vue';
import Restrictions from '@/shared/Variation/Restrictions.vue';
import { useForm } from '@inertiajs/vue3';
import KitchenStaples from '@/shared/Variation/KitchenStaples.vue';

type Ingredient = { name: string };

const props = defineProps<{
    ingredients: Ingredient[];
}>();

const form = useForm({
    restrictions: [] as string[],
    ingredients: [...props.ingredients],
    portion: [] as string[],
    servings: 1,
    kitchenStaples: false, // add boolean field
});

function submit() {
    form.post('/your-endpoint');
}
</script>

<template>
    <div class="mx-auto my-10 w-3/4 rounded bg-gray-50/50 shadow">
        <h2 class="shadow-b rounded-t bg-blue-700 p-3 text-xl font-bold text-white">Modify Recipe</h2>
        <div class="p-6">
            <IngredientVariation :ingredients="ingredients" v-model="form.ingredients" />
            <div class="flex gap-10">
                <Restrictions v-model="form.restrictions" />
                <Portion v-model="form.portion" />
                <NumberSelect v-model="form.servings" label="Number of Servings" />
                <KitchenStaples v-model="form.kitchenStaples" /> <!-- bind boolean -->
            </div>
            <div class="flex justify-end">
                <button
                    type="submit"
                    @click="submit"
                    class="mt-6 justify-end rounded bg-blue-700 px-6 py-2 text-white transition hover:bg-blue-800"
                    :disabled="form.processing"
                >
                    Submit
                </button>
            </div>
        </div>
    </div>
</template>