<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <header class="relative bg-gradient-to-r from-teal-500 via-cyan-500 to-blue-500 text-white shadow-lg" style="width:100vw; margin-left:calc(50% - 50vw);">
        <div class="mx-auto flex max-w-7xl w-full flex-col md:flex-row items-center justify-between gap-6 px-6 py-8">
            <div class="flex flex-col items-center md:items-start text-center md:text-left">
                <h2 class="mb-2 text-3xl font-extrabold drop-shadow-lg md:text-4xl">Go Premium</h2>
                <div class="mb-2 text-2xl font-bold text-yellow-300 drop-shadow-lg">
                    $3.99<span class="text-white text-base font-semibold">/month</span>
                </div>
                <p class="mb-4 text-lg font-semibold drop-shadow md:text-xl">
                    Unlock all features with Premium and enjoy a <span class="font-extrabold text-yellow-300">free 1-week trial</span>!
                </p>
            </div>

            <div class="flex-shrink-0">
                <Link
                    href="/subscription/create"
                    class="rounded-full bg-white px-6 py-2 font-bold text-teal-700 drop-shadow transition hover:bg-blue-100 focus:ring-2 focus:ring-white focus:outline-none"
                >
                    Upgrade Now
                </Link>
            </div>
        </div>
    </header>
</template>