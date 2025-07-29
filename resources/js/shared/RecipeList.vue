<script setup lang="ts">
import { computed, defineProps } from 'vue';

const props = defineProps<{
    recipes: Array<any> | { data: Array<any> }
}>();

const recipeList = computed(() => {
    if (Array.isArray(props.recipes)) {
        return props.recipes;
    }
    return props.recipes.data || [];
});
</script>

<template>
    <div class="overflow-x-auto">
        <h4 class="my-4 text-2xl font-bold">Recent Recipes</h4>
        <table class="min-w-full border border-gray-200 rounded-lg shadow">
            <thead>
            <tr class="bg-gray-100">
                <th class="px-4 py-2 font-semibold text-gray-700 text-center">Name</th>
                <th class="px-4 py-2 font-semibold text-gray-700 text-center">Created At</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="recipe in recipeList" :key="recipe.id" class="hover:bg-gray-50">
                <td class="border-t px-4 py-2 text-center">{{ recipe.name }}</td>
                <td class="border-t px-4 py-2 text-gray-500 text-center">{{ new Date(recipe.created_at).toLocaleString() }}</td>
            </tr>
            <tr v-if="recipeList.length === 0">
                <td colspan="2" class="text-center py-4 text-gray-500">No recipes found.</td>
            </tr>
            </tbody>
        </table>
    </div>
</template>