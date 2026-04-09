<script setup lang="ts">
import { Recipe } from '@/interfaces/recipe';
import { computed } from 'vue';

const props = defineProps<{ recipe: Recipe }>();

// Pick a simple letter icon based on the first ingredient keyword
const iconLetter = computed(() => {
    const name = props.recipe.name.toLowerCase();
    const map: [string[], string][] = [
        [['chicken', 'poultry', 'turkey', 'duck'], 'C'],
        [['beef', 'steak', 'burger', 'lamb'], 'B'],
        [['pork', 'bacon', 'ham'], 'P'],
        [['fish', 'salmon', 'tuna', 'cod', 'shrimp', 'seafood'], 'F'],
        [['pasta', 'spaghetti', 'linguine', 'noodle', 'penne'], 'P'],
        [['salad', 'greens', 'kale'], 'S'],
        [['soup', 'stew', 'chowder', 'broth'], 'S'],
        [['bread', 'toast', 'sandwich', 'panini'], 'B'],
        [['egg', 'omelette', 'frittata', 'quiche'], 'E'],
        [['rice', 'risotto', 'pilaf', 'curry'], 'R'],
        [['taco', 'mexican', 'burrito'], 'T'],
        [['pizza'], 'P'],
        [['vegetable', 'veggie', 'vegan', 'tofu'], 'V'],
    ];
    for (const [keywords, letter] of map) {
        if (keywords.some(k => name.includes(k))) return letter;
    }
    // Fallback: first letter of recipe name
    return props.recipe.name.charAt(0).toUpperCase();
});
</script>

<template>
    <a :href="route('recipe.show', { recipe: recipe.slug })" class="block focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#C27B5B] rounded-2xl" :aria-label="'View recipe: ' + recipe.name">
        <article class="flex items-center space-x-4 rounded-2xl bg-white dark:bg-[#222220] p-5 transition-all hover:shadow-md hover:translate-y-[-1px]">
            <div class="h-12 w-12 rounded-xl bg-[#FBF5F0] dark:bg-[#2E2E2B] flex items-center justify-center flex-shrink-0" aria-hidden="true">
                <span class="text-lg font-bold font-serif text-[#C27B5B] dark:text-[#D4967E]">{{ iconLetter }}</span>
            </div>
            <div class="min-w-0 flex-1">
                <h3 class="truncate text-base font-medium text-[#3A2520] dark:text-[#E8E0D4] group-hover:underline">
                    {{ recipe.name }}
                </h3>
                <div class="flex items-center gap-2 mt-0.5">
                    <span v-if="recipe.is_variation" class="text-xs px-2 py-0.5 rounded-full bg-[#FBF5F0] dark:bg-[#2E2E2B] text-[#C27B5B] dark:text-[#D4967E]">Variation</span>
                    <time :datetime="recipe.created_at" class="text-xs text-[#6B5C55] dark:text-[#C9B8A6]">{{ recipe.created_at }}</time>
                </div>
            </div>
            <svg class="h-4 w-4 text-[#C9B8A6] dark:text-[#6B5C55] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </article>
    </a>
</template>
