<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <header class="relative bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] shadow-lg rounded-2xl overflow-hidden">
        <div class="flex flex-col md:flex-row items-center justify-between gap-6 px-6 py-8">
            <div class="flex flex-col items-center md:items-start text-center md:text-left">
                <h2 class="mb-2 text-3xl font-extrabold font-serif md:text-4xl text-[#3A2520] dark:text-[#F3EDE6]">Need more tokens?</h2>
                <p class="mb-4 text-lg font-medium text-gray-600 dark:text-gray-300 md:text-xl">
                    Buy tokens to keep generating <span class="font-bold text-[#C27B5B]">delicious recipes</span>
                </p>
            </div>
            <div class="flex-shrink-0">
                <Link
                    href="/tokens"
                    class="rounded-full bg-[#C27B5B] hover:bg-[#A8664A] px-8 py-3 font-bold text-white transition focus:ring-2 focus:ring-[#C27B5B] focus:outline-none"
                    aria-label="Buy tokens"
                >
                    Buy Tokens
                </Link>
            </div>
        </div>
    </header>
</template>
