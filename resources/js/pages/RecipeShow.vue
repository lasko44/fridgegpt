<script setup lang="ts">
import { useHead } from '@vueuse/head';
import MyLayout from '@/layouts/MyLayout.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import VariationMenu from '@/shared/Variation/VariationMenu.vue';

interface Recipe {
    name: string;
    description: string;
    ingredients: string[];
    created_at: string;
}

interface User {
    name: string;
    is_subscribed: boolean;
    // add other user properties as needed
}
const props = defineProps<{
    recipe: Recipe;
}>();

const page = usePage();

//get the user from the page props, casting to unknown first to resolve type mismatch
const user = page.props.auth.user as unknown as User | null;

const createdAtMessage = computed(() => {
    if (props.recipe.created_at.toLowerCase() === 'today' || props.recipe.created_at.toLowerCase() === 'just now') {
        return props.recipe.created_at;
    }
    return `on ${new Date(props.recipe.created_at).toLocaleDateString()}`;
});

useHead({
    title: `${props.recipe.name} | FridgeGPT AI Recipe Creator`,
});
</script>

<template>
    <MyLayout>
        <section class="mx-auto mt-8 w-3/4 p-6 text-left text-gray-900">
            <h1 class="text-2xl font-bold">{{ recipe.name }}</h1>
            <p class="mt-2 text-gray-700">Created by: {{ user?.name || 'Guest' }} {{ createdAtMessage }}</p>
        </section>
        <section class="mx-auto mt-4 w-3/4 rounded bg-white p-6 text-left text-gray-900 shadow">
            <pre class="whitespace-pre-wrap">{{ recipe.description }}</pre>
        </section>
        <section v-if="user?.is_subscribed" id="variation-menu">
            <VariationMenu
              :recipe_description="recipe.description"
              :ingredients="recipe.ingredients.map(i => ({ name: i }))"
            />
        </section>
    </MyLayout>
</template>
