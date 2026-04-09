<script setup lang="ts">
import { useHead } from '@vueuse/head';
import MyLayout from '@/layouts/MyLayout.vue';
import { Link as InertiaLink } from '@inertiajs/vue3';

const props = defineProps<{
    balance: number;
    tokensAdded?: number;
}>();

useHead({
    title: 'Tokens Added! | FridgeGPT',
});
</script>

<template>
    <MyLayout>
        <main class="flex min-h-[60vh] items-center justify-center px-4 py-16" aria-label="Token purchase success">
            <div class="mx-auto max-w-md text-center space-y-6">
                <!-- Checkmark -->
                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-[#7A8C5A]/10">
                    <svg class="h-10 w-10 text-[#7A8C5A]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </div>

                <h1 class="text-3xl md:text-4xl font-bold font-serif text-[#3A2520] dark:text-[#EDE5DD]">Tokens added!</h1>

                <p v-if="tokensAdded" class="text-lg text-[#6B5C55] dark:text-[#C9B8A6]">
                    {{ tokensAdded }} {{ tokensAdded === 1 ? 'token has' : 'tokens have' }} been added to your account.
                </p>

                <div class="inline-flex items-center gap-3 rounded-2xl bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] px-8 py-5 shadow-sm">
                    <span class="text-4xl font-bold text-[#C27B5B]">{{ balance }}</span>
                    <span class="text-base text-[#6B5C55] dark:text-[#C9B8A6]">{{ balance === 1 ? 'token' : 'tokens' }} available</span>
                </div>

                <InertiaLink
                    href="/"
                    class="inline-block rounded-full bg-[#C27B5B] hover:bg-[#A8664A] px-8 py-3 text-white font-semibold transition focus:ring-2 focus:ring-[#C27B5B] focus:outline-none"
                    aria-label="Go home and start cooking"
                >
                    Start Cooking
                </InertiaLink>
            </div>
        </main>
    </MyLayout>
</template>
