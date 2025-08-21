<script setup lang="ts">
import { computed, defineProps } from 'vue';

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
    <div class="overflow-x-auto">
        <h4 class="my-4 text-2xl font-bold">Recent Recipes</h4>
        <table class="min-w-full rounded-lg border border-gray-200 shadow">
            <thead>
                <tr class="bg-gray-100">
                    <th class="px-4 py-2 text-center font-semibold text-gray-700">Name</th>
                    <th class="px-4 py-2 text-center font-semibold text-gray-700">Created At</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="recipe in recipeList" :key="recipe.id" class="hover:bg-gray-50">
              <td class="border-t px-4 py-2 text-center">
                  <a :href="route('recipe.show', recipe)" class="text-blue-600 hover:underline">
                      {{ recipe.name }}
                  </a>
              </td>
                    <td class="border-t px-4 py-2 text-center text-gray-500">{{ recipe.created_at }}</td>
                </tr>
                <tr v-if="recipeList.length === 0">
                    <td colspan="2" class="py-4 text-center text-gray-500">No recipes found.</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
