<script setup lang="ts">
import { ref, watch, nextTick, computed, onMounted } from 'vue';
import MyLayout from '../layouts/MyLayout.vue';
import { useHead } from '@vueuse/head';
import CtaCard from '../shared/cta-card.vue';
import Hero from '@/shared/Hero.vue';
import SubscribeModal from '@/shared/SubscribeModal.vue';
import RecipeList from '../shared/RecipeList/RecipeList.vue';
import ErrorModal from '@/shared/419ErrorModal.vue';
import { usePage } from '@inertiajs/vue3';
import CtaBanner from '@/shared/CtaBanner.vue';

useHead({
    title: 'FridgeGPT AI Recipe Creator',
    meta: [
        { name: 'description', content: 'Create delicious recipes with FridgeGPT using ingredients you have at home.' },
        { name: 'keywords', content: 'recipe, AI, cooking, ingredients, fridge' },
        { name: 'viewport', content: 'width=device-width, initial-scale=1.0' },
    ],
});
const props = defineProps<{ recipe?: string; recipes?: any[] | { data: any[]; [key: string]: any } }>();
const page = usePage();

const recipeSection = ref<HTMLElement | null>(null);

watch(
    () => props.recipe,
    async (val) => {
        if (val) {
            await nextTick();
            recipeSection.value?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    },
);

const showModal = ref(false);
const isLoggedIn = computed(() => !!page.props?.auth?.user);
const premium = computed(() => page.props?.auth?.user?.is_subscribed || false);

const hasRecipes = computed(() => {
    if (!props.recipes) return false;
    if (Array.isArray(props.recipes)) return props.recipes.length > 0;
    return Array.isArray(props.recipes.data) && props.recipes.data.length > 0;
});

// Watch for guest_limit error and show/hide modal
watch(
    () => page.props?.errors?.error,
    (val) => {
        showModal.value = !!val;
    },
    { immediate: true },
);

// Clear guest_limit error when modal closes
function handleModalClose() {
    showModal.value = false;
}

// 419 error modal logic
const show419ErrorModal = ref(page.props.show419ErrorModal || false);

function handle419ModalClose() {
    show419ErrorModal.value = false;
}

// Timezone detection and redirect if not present in URL
onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    if (!params.has('timezone')) {
        const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
        window.location.replace(
            window.location.pathname + '?timezone=' + encodeURIComponent(timezone)
        );
    }
});
</script>

<template>
    <MyLayout>
        <Hero />
        <main aria-label="App Description" class="my-5 text-center text-lg text-gray-900" tabindex="0">
            <div class="mx-auto mb-4 flex w-3/4 flex-col items-center" v-show="!hasRecipes">
                <header>
                    <h2 class="text-3xl font-extrabold">FridgeGPT Recipe Creator</h2>
                </header>
                <section>
                    <p>
                        Welcome! This app helps you create delicious recipes using any ingredients you have in your fridge or pantry. Simply enter
                        what you have, and we'll suggest a tasty, easy-to-follow recipe just for you.
                    </p>
                </section>
            </div>

            <section v-if="props.recipe" ref="recipeSection" class="mx-auto mt-8 w-11/12 rounded bg-white p-6 text-left text-gray-900 shadow">
                <h2 class="mb-2 text-2xl font-bold">Your Recipe</h2>
                <pre class="whitespace-pre-wrap">{{ props.recipe }}</pre>
            </section>

            <!-- Main area with sidebar -->
            <div class="mx-auto my-8 w-11/12 max-w-5xl">
                <div class="flex flex-col md:flex-row gap-6">
                    <!-- Primary content column -->
                    <div class="flex-1">
                        <div v-if="!premium" class="mt-10 flex flex-col items-center">
                            <div class="mx-auto mb-4 flex w-3/4 flex-col items-center" v-show="hasRecipes">
                                <header>
                                    <h2 class="text-3xl font-extrabold">FridgeGPT Recipe Creator</h2>
                                </header>
                                <section>
                                    <p>
                                        Welcome! This app helps you create delicious recipes using any ingredients you have in your fridge or pantry. Simply enter
                                        what you have, and we'll suggest a tasty, easy-to-follow recipe just for you.
                                    </p>
                                </section>
                            </div>
                            <CtaCard v-if="!hasRecipes || !recipe"/>

                        </div>

                        <div v-if="hasRecipes" class="my-8">
                            <CtaBanner v-if="recipe"/>
                            <RecipeList :recipes="props.recipes" />
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <ErrorModal v-if="show419ErrorModal" @close="handle419ModalClose" />
        <SubscribeModal v-if="showModal" :is-logged-in="isLoggedIn" @close="handleModalClose" />
    </MyLayout>
</template>