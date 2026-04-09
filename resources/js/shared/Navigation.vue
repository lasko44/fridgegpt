<script setup lang="ts">
import { Link as InertiaLink, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import type { User } from '@/interfaces/user';
import { useAppearance } from '@/composables/useAppearance';

type PageProps = {
    auth?: {
        user?: User | null;
    };
} & Record<string, any>; // allow other Inertia props (ziggy, name, etc.)

const page = usePage<PageProps>();
const loggedIn = computed(() => !!page.props.auth?.user);
const tokenBalance = computed(() => page.props.auth?.user?.token_balance ?? 0);
const mobileOpen = ref(false);
const user = computed<User | undefined>(() => page.props.auth?.user ?? undefined);


function logout() {
    router.post('/logout');
}

function toggleMobile() {
    mobileOpen.value = !mobileOpen.value;
}

const { appearance, updateAppearance } = useAppearance();

function toggleTheme() {
    if (appearance.value === 'dark') {
        updateAppearance('light');
    } else {
        updateAppearance('dark');
    }
}
</script>

<template>
    <header class="bg-white dark:bg-[#1A1A18] border-b border-[#EDE5DD] dark:border-[#262624] font-sans" role="banner">
        <nav class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3 sm:px-8 md:py-4" aria-label="Main navigation">
            <div class="flex items-center gap-8">
                <InertiaLink href="/" class="text-[#3A2520] dark:text-[#EDE5DD] hover:opacity-80 transition" aria-label="FridgeGPT home">
                    <span class="text-xl sm:text-2xl leading-none font-bold tracking-tight font-serif">FridgeGPT</span>
                </InertiaLink>

                <!-- Desktop Links -->
                <div class="hidden items-center gap-6 md:flex">
                    <template v-if="!loggedIn">
                        <InertiaLink href="/signup" class="text-base font-medium text-[#6B5C55] dark:text-[#C9B8A6] transition hover:text-[#C27B5B] dark:hover:text-[#C27B5B]"> Sign Up </InertiaLink>
                        <InertiaLink href="/login" class="text-base font-medium text-[#6B5C55] dark:text-[#C9B8A6] transition hover:text-[#C27B5B] dark:hover:text-[#C27B5B]"> Login </InertiaLink>
                    </template>
                    <template v-else>
                        <InertiaLink href="/recipes" class="text-base font-medium text-[#6B5C55] dark:text-[#C9B8A6] transition hover:text-[#C27B5B] dark:hover:text-[#C27B5B]">
                            Recipes
                        </InertiaLink>
                        <InertiaLink href="/tokens" class="text-base font-medium text-[#6B5C55] dark:text-[#C9B8A6] transition hover:text-[#C27B5B] dark:hover:text-[#C27B5B]">
                            Tokens
                        </InertiaLink>
                        <InertiaLink
                            :href="user ? route('user.edit', { user: user.username }) : '#'"
                            class="text-base font-medium text-[#6B5C55] dark:text-[#C9B8A6] transition hover:text-[#C27B5B] dark:hover:text-[#C27B5B]"
                        >
                            Account
                        </InertiaLink>
                        <button
                            @click="logout"
                            class="text-base font-medium text-[#6B5C55] dark:text-[#C9B8A6] hover:cursor-pointer transition hover:text-[#C27B5B] dark:hover:text-[#C27B5B] focus:outline-none"
                            aria-label="Logout"
                        >
                            Logout
                        </button>
                    </template>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <!-- Theme toggle -->
                <button
                    @click="toggleTheme"
                    class="rounded-full p-2.5 text-[#6B5C55] dark:text-[#C9B8A6] hover:text-[#3A2520] dark:hover:text-[#EDE5DD] hover:bg-[#FBF5F0] dark:hover:bg-[#2E2E2B] transition focus:outline-none focus:ring-2 focus:ring-[#C27B5B]"
                    :aria-label="appearance === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'"
                >
                    <!-- Sun (shown in dark mode) -->
                    <svg v-if="appearance === 'dark'" class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"/>
                    </svg>
                    <!-- Moon (shown in light mode) -->
                    <svg v-else class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/>
                    </svg>
                </button>

                <!-- Token balance / Buy Tokens (desktop) -->
                <div class="hidden md:flex items-center gap-3">
                    <template v-if="loggedIn">
                        <span class="rounded-full bg-[#FBF5F0] dark:bg-[#2E2E2B] px-3 py-1.5 text-sm font-medium text-[#C27B5B] dark:text-[#D4967E]">
                            {{ tokenBalance }} {{ tokenBalance === 1 ? 'token' : 'tokens' }}
                        </span>
                    </template>
                    <InertiaLink
                        href="/tokens"
                        class="rounded-full bg-[#C27B5B] hover:bg-[#A8664A] px-5 py-2 font-semibold text-white transition focus:ring-2 focus:ring-[#C27B5B] focus:outline-none"
                    >
                        Buy Tokens
                    </InertiaLink>
                </div>

                <!-- Mobile menu button -->
                <button
                    @click="toggleMobile"
                    class="inline-flex items-center justify-center rounded-md p-2 text-[#3A2520] dark:text-[#E8E0D4] focus:ring-2 focus:ring-[#C27B5B] focus:outline-none md:hidden"
                    :aria-expanded="mobileOpen"
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
            <div v-show="mobileOpen" class="bg-white dark:bg-[#1A1A18] border-b border-[#EDE5DD] dark:border-[#262624] md:hidden">
                <div class="space-y-4 px-4 pt-4 pb-6">
                    <template v-if="!loggedIn">
                        <InertiaLink href="/signup" class="block text-base font-medium text-[#3A2520] dark:text-[#EDE5DD] hover:text-[#C27B5B] dark:hover:text-[#C27B5B]"> Sign Up </InertiaLink>
                        <InertiaLink href="/login" class="block text-base font-medium text-[#3A2520] dark:text-[#EDE5DD] hover:text-[#C27B5B] dark:hover:text-[#C27B5B]"> Login </InertiaLink>
                    </template>
                    <template v-else>
                        <InertiaLink href="/recipes" class="block text-base font-medium text-[#3A2520] dark:text-[#EDE5DD] hover:text-[#C27B5B] dark:hover:text-[#C27B5B]"> Recipes </InertiaLink>
                        <InertiaLink href="/tokens" class="block text-base font-medium text-[#3A2520] dark:text-[#EDE5DD] hover:text-[#C27B5B] dark:hover:text-[#C27B5B]"> Tokens </InertiaLink>
                        <InertiaLink :href="user ? route('user.edit', { user: user.username }) : '#'" class="block text-base font-medium text-[#3A2520] dark:text-[#EDE5DD] hover:text-[#C27B5B] dark:hover:text-[#C27B5B]"> Account </InertiaLink>
                        <button @click="logout" class="w-full text-left text-base font-medium text-[#3A2520] dark:text-[#EDE5DD] hover:text-[#C27B5B] dark:hover:text-[#C27B5B] focus:outline-none">
                            Logout
                        </button>
                    </template>

                    <div class="border-t border-white/20 pt-2">
                        <template v-if="loggedIn">
                            <span class="block text-center text-sm font-medium text-[#C27B5B] dark:text-[#D4967E] mt-2">
                                {{ tokenBalance }} {{ tokenBalance === 1 ? 'token' : 'tokens' }}
                            </span>
                        </template>
                        <InertiaLink
                            href="/tokens"
                            class="mt-3 inline-block w-full rounded-full bg-[#C27B5B] hover:bg-[#A8664A] px-4 py-2 text-center font-bold text-white"
                        >
                            Buy Tokens
                        </InertiaLink>
                    </div>
                </div>
            </div>
        </transition>
    </header>
</template>
