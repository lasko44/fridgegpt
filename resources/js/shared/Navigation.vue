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
        <nav class="mx-auto flex max-w-5xl items-center justify-between px-4 py-4 sm:px-8 md:py-6">
            <div class="flex items-center gap-6">
                <InertiaLink href="/" class="flex items-center gap-1 text-white hover:text-cyan-200">
                    <img src="/images/logo.webp" alt="FridgeGPT logo" class="h-8 w-8 rounded-sm object-contain drop-shadow-lg sm:h-10 sm:w-10" />
                    <span class="text-2xl leading-none font-extrabold drop-shadow-lg">FridgeGPT</span>
                </InertiaLink>

                <!-- Desktop Links -->
                <div class="hidden items-center gap-6 md:flex">
                    <template v-if="!loggedIn">
                        <InertiaLink href="/signup" class="text-lg font-semibold text-white transition hover:text-cyan-200"> Sign Up </InertiaLink>
                        <InertiaLink href="/login" class="text-lg font-semibold text-white transition hover:text-cyan-200"> Login </InertiaLink>
                    </template>
                    <template v-else>
                        <InertiaLink href="/account" class="text-lg font-semibold text-white transition hover:text-cyan-200"> Account </InertiaLink>
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
                    class="inline-flex items-center justify-center rounded-md p-2 text-white focus:ring-2 focus:ring-white focus:outline-none md:hidden"
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
            <div v-show="mobileOpen" class="bg-gradient-to-r from-teal-500 via-cyan-500 to-blue-500 md:hidden">
                <div class="space-y-4 px-4 pt-4 pb-6">
                    <template v-if="!loggedIn">
                        <InertiaLink href="/signup" class="block text-lg font-semibold text-white hover:text-cyan-200"> Sign Up </InertiaLink>
                        <InertiaLink href="/login" class="block text-lg font-semibold text-white hover:text-cyan-200"> Login </InertiaLink>
                    </template>
                    <template v-else>
                        <InertiaLink href="/account" class="block text-lg font-semibold text-white hover:text-cyan-200"> Account </InertiaLink>
                        <InertiaLink href="/recipes" class="block text-lg font-semibold text-white hover:text-cyan-200"> My Recipes </InertiaLink>
                        <button @click="logout" class="w-full text-left text-lg font-semibold text-white hover:text-cyan-200 focus:outline-none">
                            Logout
                        </button>
                    </template>

                    <div class="border-t border-white/20 pt-2">
                        <template v-if="!loggedIn || !isPremium">
                            <InertiaLink
                                href="/subscription/create"
                                class="mt-3 inline-block w-full rounded-full bg-yellow-300 px-4 py-2 text-center font-bold text-teal-700 drop-shadow hover:bg-yellow-400"
                            >
                                Go Premium – First Week Free
                            </InertiaLink>
                        </template>
                        <template v-else>
                            <span
                                class="mt-3 inline-block w-full rounded-full bg-yellow-300 px-4 py-2 text-center font-bold text-teal-700 drop-shadow"
                                >Premium</span
                            >
                        </template>
                    </div>
                </div>
            </div>
        </transition>
    </header>
</template>
