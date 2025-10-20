<script setup lang="ts">
import { Recipe } from '@/interfaces/recipe';

defineProps<{ recipe: Recipe }>();
</script>

<template>
    <a :href="route('recipe.show', { recipe: recipe.slug })" class="block">
        <article class="flex items-center space-x-4 rounded-2xl bg-white p-4 shadow-md transition-shadow hover:shadow-lg">
            <figure class="flex-shrink-0">
                <img :src="recipe.image_url" :alt="recipe.name" class="h-14 w-14 rounded-xl object-cover" />
            </figure>
            <div class="min-w-0 flex-1 flex items-center justify-between">
                <h3 class="cursor-pointer truncate text-base font-medium text-blue-700 hover:underline mr-4">
                     {{ recipe.name }}
                </h3>
                <time :datetime="recipe.created_at" class="text-sm text-gray-500 flex-shrink-0">{{ recipe.created_at }}</time>
            </div>
        </article>
    </a>

</template>

<style scoped></style>
