<script setup lang="ts">
import { useHead } from '@vueuse/head';
import MyLayout from '@/layouts/MyLayout.vue';
import { usePage } from '@inertiajs/vue3';
import { computed, provide} from 'vue';
import VariationMenu from '@/shared/Variation/VariationMenu.vue';
import { Recipe } from '@/interfaces/recipe';
import { User } from '@/interfaces/user';

const props = defineProps<{
    recipe: Recipe;
}>();

const page = usePage();

const recipe = props.recipe;
provide('recipe', recipe);



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
     <div class="flex flex-col md:flex-row max-w-5xl mx-auto">
         <div class="flex-1 px-8">
             <section class="mx-auto mt-8 w-full text-left text-gray-900">
                 <h1 class="text-2xl font-bold">{{ recipe.name }}</h1>
                 <p class="mt-2 text-gray-700">Created by: {{ user?.name || 'Guest' }} {{ createdAtMessage }}</p>
             </section>
             <section class="mx-auto mt-4 w-full rounded bg-white p-6 text-left text-gray-900 shadow">
                 <pre class="whitespace-pre-wrap">{{ recipe.description }}</pre>
             </section>
             <section v-if="user?.is_subscribed" id="variation-menu">
                 <VariationMenu :recipe_description="recipe.description" :ingredients="recipe.ingredients" />
             </section>
         </div>
     </div>

    </MyLayout>
</template>
