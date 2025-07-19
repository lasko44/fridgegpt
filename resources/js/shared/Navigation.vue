<script setup lang="ts">
import { computed } from 'vue';
import { usePage, router, Link as InertiaLink } from '@inertiajs/vue3';

const page = usePage();
const loggedIn = computed(() => !!page.props.auth?.user);

function logout() {
    router.post('/logout');
}
</script>

<template>
    <header class="bg-gradient-to-r from-teal-500 via-cyan-500 to-blue-500">
        <nav class="max-w-5xl mx-auto flex items-center justify-between py-6 px-8">
            <div class="flex gap-10 items-center">
                <InertiaLink href="/" class="text-2xl font-extrabold text-white drop-shadow-lg hover:text-cyan-200 transition">FridgeGPT</InertiaLink>
                <template v-if="!loggedIn">
                    <InertiaLink href="/signup" class="text-lg hover:cursor-pointer font-semibold text-white hover:text-cyan-200 transition">Sign Up</InertiaLink>
                    <InertiaLink href="/login" class="text-lg hover:cursor-pointer font-semibold text-white hover:text-cyan-200 transition">Login</InertiaLink>
                </template>
                <template v-else>
                    <InertiaLink href="/account" class="text-lg hover:cursor-pointer font-semibold text-white hover:text-cyan-200 transition">Account Settings</InertiaLink>
                    <button
                        @click="logout"
                        class="text-lg hover:cursor-pointer font-semibold text-white hover:text-cyan-200 transition ml-4 focus:outline-none"
                        aria-label="Logout"
                    >
                        Logout
                    </button>
                </template>
            </div>
        </nav>
    </header>
</template>

<style scoped>
header {
    font-size: 1.15rem;
}
</style>