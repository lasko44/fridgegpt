<script setup lang="ts">
import { Link as InertiaLink, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const loggedIn = computed(() => !!page.props.auth?.user);
const isPremium = computed(() => page.props.auth?.user?.is_subscribed);
const mobileOpen = ref(false);

function logout() {
    router.post('/logout');
}

function toggleMobile() {
    mobileOpen.value = !mobileOpen.value;
}
</script>

<template>
    <header class="bg-gradient-to-r from-teal-500 via-cyan-500 to-blue-500">
        <nav class="mx-auto max-w-5xl px-4 sm:px-8 py-4 md:py-6 flex items-center justify-between">
            <div class="flex items-center gap-6">
                <InertiaLink href="/" class="text-2xl font-extrabold text-white drop-shadow-lg transition hover:text-cyan-200">
                    FridgeGPT
                </InertiaLink>

                <!-- Desktop Links -->
                <div class="hidden md:flex items-center gap-6">
                    <template v-if="!loggedIn">
                        <InertiaLink href="/signup" class="text-lg font-semibold text-white transition hover:text-cyan-200">
                            Sign Up
                        </InertiaLink>
                        <InertiaLink href="/login" class="text-lg font-semibold text-white transition hover:text-cyan-200">
                            Login
                        </InertiaLink>
                    </template>
                    <template v-else>
                        <InertiaLink href="/account" class="text-lg font-semibold text-white transition hover:text-cyan-200">
                            Account
                        </InertiaLink>
                        <InertiaLink href="/recipes" class="text-lg font-semibold text-white transition hover:text-cyan-200">
                            My Recipes
                        </InertiaLink>
                        <button
                            @click="logout"
                            class="ml-2 text-lg font-semibold text-white transition hover:text-cyan-200 focus:outline-none"
                            aria-label="Logout"
                        >
                            Logout
                        </button>
                    </template>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <!-- Premium / CTA (desktop) -->
                <div class="hidden md:block">
                    <template v-if="!loggedIn || !isPremium">
                        <InertiaLink
                            href="/subscription/create"
                            class="ml-6 rounded-full bg-yellow-300 px-5 py-2 font-bold text-teal-700 drop-shadow transition hover:bg-yellow-400 focus:ring-2 focus:ring-white focus:outline-none"
                        >
                            Go Premium – First Week Free
                        </InertiaLink>
                    </template>
                    <template v-else>
                        <span class="ml-6 rounded-full bg-yellow-300 px-5 py-2 font-bold text-teal-700 drop-shadow transition"> Premium </span>
                    </template>
                </div>

                <!-- Mobile menu button -->
                <button
                    @click="toggleMobile"
                    class="md:hidden inline-flex items-center justify-center p-2 rounded-md text-white focus:outline-none focus:ring-2 focus:ring-white"
                    :aria-expanded="mobileOpen.toString()"
                    aria-label="Toggle navigation"
                >
                    <svg v-if="!mobileOpen" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg v-else class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </nav>

        <!-- Mobile menu -->
        <transition
            enter-active-class="transition transform duration-150 ease-out"
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition transform duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-2"
        >
            <div v-show="mobileOpen" class="md:hidden bg-gradient-to-r from-teal-500 via-cyan-500 to-blue-500">
                <div class="px-4 pt-4 pb-6 space-y-4">
                    <template v-if="!loggedIn">
                        <InertiaLink href="/signup" class="block text-white text-lg font-semibold hover:text-cyan-200">
                            Sign Up
                        </InertiaLink>
                        <InertiaLink href="/login" class="block text-white text-lg font-semibold hover:text-cyan-200">
                            Login
                        </InertiaLink>
                    </template>
                    <template v-else>
                        <InertiaLink href="/account" class="block text-white text-lg font-semibold hover:text-cyan-200">
                            Account
                        </InertiaLink>
                        <InertiaLink href="/recipes" class="block text-white text-lg font-semibold hover:text-cyan-200">
                            My Recipes
                        </InertiaLink>
                        <button
                            @click="logout"
                            class="w-full text-left text-white text-lg font-semibold hover:text-cyan-200 focus:outline-none"
                        >
                            Logout
                        </button>
                    </template>

                    <div class="pt-2 border-t border-white/20">
                        <template v-if="!loggedIn || !isPremium">
                            <InertiaLink
                                href="/subscription/create"
                                class="mt-3 inline-block w-full text-center rounded-full bg-yellow-300 px-4 py-2 font-bold text-teal-700 drop-shadow hover:bg-yellow-400"
                            >
                                Go Premium – First Week Free
                            </InertiaLink>
                        </template>
                        <template v-else>
                            <span class="mt-3 inline-block w-full text-center rounded-full bg-yellow-300 px-4 py-2 font-bold text-teal-700 drop-shadow">Premium</span>
                        </template>
                    </div>
                </div>
            </div>
        </transition>
    </header>
</template>
