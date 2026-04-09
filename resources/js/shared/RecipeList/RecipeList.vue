<script setup lang="ts">
import { computed, defineProps } from 'vue';
import RecipeItem from '@/shared/RecipeList/RecipeItem.vue';

const props = defineProps<{
    recipes: Array<any> | { data: Array<any> };
}>();

const recipeList = computed(() => {
    if (Array.isArray(props.recipes)) {
        return props.recipes;
    }
    return props.recipes.data || [];
});
</script>

<template>
    <div class="overflow-x-auto" role="region" aria-label="Recipe list">
        <h4 class="my-4 text-2xl font-bold text-[#3A2520] dark:text-[#F3EDE6]">Your Recent Recipes</h4>
        <ul class="min-w-full rounded-lg" role="list">
           <li v-for="recipe in recipeList" :key="recipe.id" class="py-2">
                <RecipeItem :recipe="recipe" />
              </li>
        </ul>
    </div>
</template>
