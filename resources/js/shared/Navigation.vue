<script setup lang="ts">
import { Link as InertiaLink, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const loggedIn = computed(() => !!page.props.auth?.user);
const isPremium = computed(() => page.props.auth?.user?.subscribed);

function logout() {
    router.post('/logout');
}
</script>

<template>
    <header class="bg-gradient-to-r from-teal-500 via-cyan-500 to-blue-500">
        <nav class="mx-auto flex max-w-5xl items-center justify-between px-8 py-6">
            <div class="flex items-center gap-10">
                <InertiaLink href="/" class="text-2xl font-extrabold text-white drop-shadow-lg transition hover:text-cyan-200">
                    FridgeGPT
                </InertiaLink>
                <template v-if="!loggedIn">
                    <InertiaLink href="/signup" class="text-lg font-semibold text-white transition hover:cursor-pointer hover:text-cyan-200">
                        Sign Up
                    </InertiaLink>
                    <InertiaLink href="/login" class="text-lg font-semibold text-white transition hover:cursor-pointer hover:text-cyan-200">
                        Login
                    </InertiaLink>
                </template>
                <template v-else>
                    <InertiaLink href="/account" class="text-lg font-semibold text-white transition hover:cursor-pointer hover:text-cyan-200">
                        Account
                    </InertiaLink>
                    <button
                        @click="logout"
                        class="ml-4 text-lg font-semibold text-white transition hover:cursor-pointer hover:text-cyan-200 focus:outline-none"
                        aria-label="Logout"
                    >
                        Logout
                    </button>
                </template>
            </div>
            <div>
                <template v-if="!loggedIn">
                    <InertiaLink
                        href="/subscription/create"
                        class="ml-6 rounded-full bg-yellow-300 px-5 py-2 font-bold text-teal-700 drop-shadow transition hover:bg-yellow-400 focus:ring-2 focus:ring-white focus:outline-none"
                    >
                        Go Premium – First Week Free
                    </InertiaLink>
                </template>
                <template v-else>
                    <span class="ml-6 rounded-full bg-yellow-300  px-5 py-2 font-bold text-teal-700 drop-shadow transition"> Premium </span>
                </template>
            </div>
        </nav>
    </header>
</template>
