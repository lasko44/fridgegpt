<script setup lang="ts">
import IngredientVariation from '@/shared/Variation/IngredientVariation.vue';
import Restrictions from '@/shared/Variation/Restrictions.vue';

type Ingredient = { name: string };

//define props for an ingredients array
defineProps<{
    ingredients: Ingredient[];
}>();
</script>

<template>
    <div class="mx-auto mt-10 w-3/4 bg-gray-50/60 rounded shadow">
        <h2 class="text-xl font-bold text-white bg-blue-700 p-3 rounded-t shadow-b">Modify Recipe</h2>
        <div class="p-6">
            <IngredientVariation :ingredients="ingredients" />
            <Restrictions/>
        </div>

    </div>
</template>

<style scoped></style>
