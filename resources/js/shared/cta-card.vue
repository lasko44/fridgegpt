<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const loggedIn = computed(() => !!page.props.auth?.user);
const ctaHref = computed(() => (loggedIn.value ? '/account' : '/signup'));
const ctaText = computed(() => (loggedIn.value ? 'Go to Account Settings' : 'Sign Up for Premium'));
</script>

<template>
    <div
        class="mx-auto flex max-w-sm flex-col items-center rounded-xl bg-gradient-to-r from-teal-500 via-cyan-500 to-blue-500 p-8 text-white shadow-lg"
    >
        <img src="/images/logo.webp" alt="Logo" class="mb-4 h-24 w-24 drop-shadow-lg" />
        <h2 class="mb-2 text-3xl font-extrabold drop-shadow-lg md:text-4xl">Go Premium</h2>
        <p class="mb-6 text-center text-lg font-semibold text-white drop-shadow md:text-xl">Unlock all features with Premium:</p>
        <ul class="mb-6 w-full text-base font-bold md:text-lg">
            <li class="mb-2 flex items-center">
                <span class="mr-2 text-green-300 drop-shadow">&#10003;</span>
                <span class="drop-shadow">Unlimited recipes</span>
            </li>
            <li class="mb-2 flex items-center">
                <span class="mr-2 text-green-300 drop-shadow">&#10003;</span>
                <span class="drop-shadow">Save recipes</span>
            </li>
            <li class="mb-2 flex items-center">
                <span class="mr-2 text-green-300 drop-shadow">&#10003;</span>
                <span class="drop-shadow">Recipe variations</span>
            </li>
            <li class="flex items-center">
                <span class="mr-2 text-green-300 drop-shadow">&#10003;</span>
                <span class="drop-shadow">Favorite recipes</span>
            </li>
        </ul>
        <Link
            :href="ctaHref"
            class="rounded-full bg-white px-6 py-2 font-bold text-teal-700 drop-shadow transition hover:bg-blue-100 focus:ring-2 focus:ring-white focus:outline-none"
        >
            {{ ctaText }}
        </Link>
    </div>
</template>
