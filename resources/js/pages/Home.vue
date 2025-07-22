<script setup lang="ts">
import { ref, watch, nextTick, computed } from 'vue'
import MyLayout from '../layouts/MyLayout.vue'
import { useHead } from '@vueuse/head'
import CtaCard from '../shared/cta-card.vue'
import Hero from '@/shared/Hero.vue'
import SubscribeModal from '@/shared/SubscribeModal.vue'
import { usePage } from '@inertiajs/vue3'

useHead({
    title: 'FridgeGPT AI Recipe Creator',
    meta: [
        { name: 'description', content: 'Create delicious recipes with FridgeGPT using ingredients you have at home.' },
        { name: 'keywords', content: 'recipe, AI, cooking, ingredients, fridge' },
        { name: 'viewport', content: 'width=device-width, initial-scale=1.0' },
    ],
})
const props = defineProps<{ recipe?: string }>()
const page = usePage()

const recipeSection = ref<HTMLElement | null>(null)

watch(() => props.recipe, async (val) => {
    if (val) {
        await nextTick()
        recipeSection.value?.scrollIntoView({ behavior: 'smooth', block: 'center' })
    }
})

// Check for guest_limit error
const showSubscribeModal = computed(() => !!page.props?.errors?.guest_limit)
const isLoggedIn = computed(() => !!page.props?.auth?.user)
</script>

<template>
    <MyLayout>
        <Hero/>
        <main aria-label="App Description" class="text-lg text-gray-900 text-center my-5" tabindex="0">
            <div class="mb-4 flex flex-col items-center w-3/4 mx-auto">
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
            <section
                v-if="props.recipe"
                ref="recipeSection"
                class="mt-8 rounded mx-auto bg-white p-6 text-gray-900 shadow text-left w-3/4"
            >
                <h2 class="mb-2 text-2xl font-bold">Your Recipe</h2>
                <pre class="whitespace-pre-wrap">{{ props.recipe }}</pre>
            </section>
            <div v-if="!$attrs.userLoggedIn" class="mt-10 flex justify-center">
                <CtaCard />
            </div>
        </main>
        <SubscribeModal v-if="showSubscribeModal" :is-logged-in="isLoggedIn" />
    </MyLayout>
</template>