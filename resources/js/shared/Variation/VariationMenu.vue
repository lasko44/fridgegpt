<script setup lang="ts">
import IngredientVariation from '@/shared/Variation/IngredientVariation.vue';
import Restrictions from '@/shared/Variation/Restrictions.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

type Ingredient = { name: string };

const props = defineProps<{
    ingredients: Ingredient[];
    recipe_description: string;
    recipe_slug: string;
}>();

const isOpen = ref(false);

const form = useForm({
    recipe_slug: props.recipe_slug,
    restrictions: [] as string[],
    ingredients: [...props.ingredients],
    recipe_description: props.recipe_description,
});

function submit() {
    form.post(route('variation.store'));
}
</script>

<template>
    <div class="rounded-xl bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] shadow-sm overflow-hidden">
        <!-- Collapsible header -->
        <button
            @click="isOpen = !isOpen"
            class="w-full flex items-center justify-between px-6 py-4 text-left hover:bg-[#FBF5F0] dark:hover:bg-[#2E2E2B]/60 transition-colors"
            :aria-expanded="isOpen"
            aria-label="Toggle recipe modification panel"
        >
            <div class="flex items-center gap-3">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#FFF8F3] dark:bg-[#2E2E2B]">
                    <svg class="h-4 w-4 text-[#C27B5B] dark:text-[#D4967E]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </span>
                <div>
                    <h2 class="text-base font-semibold text-[#3A2520] dark:text-[#F3EDE6]">Modify Recipe</h2>
                    <p class="text-xs text-[#6B5C55] dark:text-[#C9B8A6]">Swap ingredients and dietary restrictions</p>
                </div>
            </div>
            <svg
                class="h-5 w-5 text-gray-400 transition-transform duration-200"
                :class="{ 'rotate-180': isOpen }"
                fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <!-- Expandable content -->
        <div v-show="isOpen" class="border-t border-[#EDE5DD] dark:border-[#3D3D39] px-6 py-5 space-y-6">

            <!-- Ingredients -->
            <IngredientVariation :ingredients="ingredients" v-model="form.ingredients" />

            <!-- Dietary Restrictions -->
            <Restrictions v-model="form.restrictions" />

            <!-- Submit -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-[#EDE5DD] dark:border-[#3D3D39]">
                <span class="text-xs text-[#6B5C55] dark:text-[#C9B8A6]">Creates a new variation of this recipe</span>
                <button
                    @click="submit"
                    :disabled="form.processing"
                    class="rounded-full bg-[#C27B5B] hover:bg-[#A8664A] px-6 py-2.5 text-sm font-semibold text-white transition focus:outline-none focus:ring-2 focus:ring-[#C27B5B] disabled:opacity-50"
                    aria-label="Generate recipe variation"
                >
                    {{ form.processing ? 'Generating...' : 'Generate Variation' }}
                </button>
            </div>
        </div>
    </div>
</template>
