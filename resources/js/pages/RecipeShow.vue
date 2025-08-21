<script setup lang="ts">
import { useHead } from '@vueuse/head';
import MyLayout from '@/layouts/MyLayout.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Recipe {
    name: string;
    description: string;
    ingredients: string[];
    created_at: string;
}

const props = defineProps<{
    recipe: Recipe;
}>();

const page = usePage();

//get the user from the page props
const user = page.props.auth.user;

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
        <section class="mx-auto mt-8 w-3/4 rounded bg-white p-6 text-left text-gray-900 shadow">
            <pre class="whitespace-pre-wrap">{{ recipe.description }}</pre>
        </section>
    </MyLayout>
</template>
