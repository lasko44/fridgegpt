<script setup lang="ts">
import { Link as InertiaLink, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import type { User } from '@/interfaces/user';
import { useAppearance } from '@/composables/useAppearance';

type PageProps = {
    auth?: {
        user?: User | null;
        unread_count?: number;
        support_unread?: number;
    };
} & Record<string, any>;

const page = usePage<PageProps>();
const loggedIn = computed(() => !!page.props.auth?.user);
const tokenBalance = computed(() => page.props.auth?.user?.token_balance ?? 0);
const unreadCount = computed(() => page.props.auth?.unread_count ?? 0);
const supportUnread = computed(() => page.props.auth?.support_unread ?? 0);
const user = computed<User | undefined>(() => page.props.auth?.user ?? undefined);

const mobileOpen = ref(false);
const accountOpen = ref(false);
const accountRef = ref<HTMLElement | null>(null);

function logout() {
    router.post('/logout');
}

function toggleMobile() {
    mobileOpen.value = !mobileOpen.value;
}

function closeAccount(event: MouseEvent) {
    if (accountRef.value && !accountRef.value.contains(event.target as Node)) {
        accountOpen.value = false;
    }
}

onMounted(() => document.addEventListener('click', closeAccount));
onBeforeUnmount(() => document.removeEventListener('click', closeAccount));

const { appearance, updateAppearance } = useAppearance();

function toggleTheme() {
    updateAppearance(appearance.value === 'dark' ? 'light' : 'dark');
}
</script>

<template>
    <header class="bg-white dark:bg-[#1A1A18] border-b border-[#EDE5DD] dark:border-[#262624] font-sans" role="banner">
        <nav class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3 sm:px-8 md:py-4" aria-label="Main navigation">
            <!-- Logo + primary links -->
            <div class="flex items-center gap-8">
                <InertiaLink href="/" class="flex items-center gap-2 hover:opacity-80 transition" aria-label="FridgeGPT home">
                    <img
                        src="/images/logo.webp"
                        alt=""
                        class="h-7 w-7 sm:h-8 sm:w-8"
                        style="filter: sepia(1) saturate(3) hue-rotate(340deg) brightness(0.55);"
                        aria-hidden="true"
                    />
                    <span class="text-xl sm:text-2xl leading-none font-bold tracking-tight font-serif text-[#3A2520] dark:text-[#EDE5DD]">FridgeGPT</span>
                </InertiaLink>

                <!-- Desktop primary links (logged-in users only) -->
                <div v-if="loggedIn" class="hidden items-center gap-6 md:flex">
                    <InertiaLink href="/recipes" class="text-base font-medium text-[#6B5C55] dark:text-[#C9B8A6] transition hover:text-[#C27B5B] dark:hover:text-[#C27B5B]">
                        Recipes
                    </InertiaLink>
                    <InertiaLink href="/meal-plans" class="text-base font-medium text-[#6B5C55] dark:text-[#C9B8A6] transition hover:text-[#C27B5B] dark:hover:text-[#C27B5B]">
                        Meal Plans
                    </InertiaLink>
                </div>
            </div>

            <!-- Right side -->
            <div class="flex items-center gap-3">
                <!-- Theme toggle -->
                <button
                    @click="toggleTheme"
                    class="rounded-full p-2.5 text-[#6B5C55] dark:text-[#C9B8A6] hover:text-[#3A2520] dark:hover:text-[#EDE5DD] hover:bg-[#FBF5F0] dark:hover:bg-[#2E2E2B] transition focus:outline-none focus:ring-2 focus:ring-[#C27B5B]"
                    :aria-label="appearance === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'"
                >
                    <svg v-if="appearance === 'dark'" class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"/>
                    </svg>
                    <svg v-else class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/>
                    </svg>
                </button>

                <!-- Logged out -->
                <template v-if="!loggedIn">
                    <InertiaLink href="/login" class="hidden md:inline-block text-base font-medium text-[#6B5C55] dark:text-[#C9B8A6] hover:text-[#C27B5B] transition">
                        Login
                    </InertiaLink>
                    <InertiaLink
                        href="/signup"
                        class="rounded-full bg-[#C27B5B] hover:bg-[#A8664A] px-5 py-2 font-semibold text-white transition focus:ring-2 focus:ring-[#C27B5B] focus:outline-none"
                    >
                        Sign Up
                    </InertiaLink>
                </template>

                <!-- Logged in -->
                <template v-else>
                    <!-- Token balance pill -->
                    <InertiaLink
                        href="/tokens"
                        class="hidden md:inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-semibold bg-[#FBF5F0] dark:bg-[#2E2E2B] text-[#C27B5B] hover:bg-[#EDE5DD] dark:hover:bg-[#3D3D39] transition"
                        aria-label="Token balance"
                    >
                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.736 6.979C9.208 6.193 9.696 6 10 6c.304 0 .792.193 1.264.979a1 1 0 001.715-1.029C12.279 4.784 11.232 4 10 4s-2.279.784-2.979 1.95c-.285.475-.507 1-.67 1.55H6a1 1 0 000 2h.013a9.358 9.358 0 000 1H6a1 1 0 100 2h.351c.163.55.385 1.075.67 1.55C7.721 15.216 8.768 16 10 16s2.279-.784 2.979-1.95a1 1 0 10-1.715-1.029C10.792 13.807 10.304 14 10 14c-.304 0-.792-.193-1.264-.979a5.5 5.5 0 01-.354-.521H10a1 1 0 100-2H7.958a7.532 7.532 0 010-1H10a1 1 0 100-2H8.382c.094-.18.199-.362.354-.521z"/>
                        </svg>
                        {{ tokenBalance }}
                    </InertiaLink>

                    <!-- Notification bell -->
                    <InertiaLink
                        href="/inbox"
                        class="hidden md:inline-flex relative items-center p-2.5 rounded-full text-[#6B5C55] dark:text-[#C9B8A6] hover:text-[#C27B5B] hover:bg-[#FBF5F0] dark:hover:bg-[#2E2E2B] transition"
                        aria-label="Notifications"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <span
                            v-if="unreadCount > 0"
                            class="absolute top-1 right-1 h-4 min-w-4 px-1 rounded-full bg-[#C27B5B] text-white text-[10px] font-bold flex items-center justify-center"
                        >
                            {{ unreadCount > 9 ? '9+' : unreadCount }}
                        </span>
                    </InertiaLink>

                    <!-- Account dropdown -->
                    <div ref="accountRef" class="relative hidden md:block">
                        <button
                            @click="accountOpen = !accountOpen"
                            class="inline-flex items-center gap-2 rounded-full pl-2 pr-3 py-1.5 hover:bg-[#FBF5F0] dark:hover:bg-[#2E2E2B] transition focus:outline-none focus:ring-2 focus:ring-[#C27B5B]"
                            :aria-expanded="accountOpen"
                            aria-haspopup="menu"
                            aria-label="Account menu"
                        >
                            <span class="h-8 w-8 rounded-full bg-[#C27B5B] flex items-center justify-center text-white text-sm font-bold">
                                {{ user?.name?.charAt(0)?.toUpperCase() }}
                            </span>
                            <svg class="h-4 w-4 text-[#6B5C55] dark:text-[#C9B8A6] transition" :class="{ 'rotate-180': accountOpen }" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <transition
                            enter-active-class="transition duration-150 ease-out"
                            enter-from-class="opacity-0 -translate-y-1"
                            enter-to-class="opacity-100 translate-y-0"
                            leave-active-class="transition duration-100 ease-in"
                            leave-from-class="opacity-100 translate-y-0"
                            leave-to-class="opacity-0 -translate-y-1"
                        >
                            <div
                                v-if="accountOpen"
                                class="absolute right-0 mt-2 w-64 rounded-xl bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] shadow-lg overflow-hidden z-50"
                                role="menu"
                            >
                                <!-- Profile header -->
                                <div class="px-4 py-3 border-b border-[#EDE5DD] dark:border-[#3D3D39]">
                                    <p class="font-semibold text-sm text-[#3A2520] dark:text-[#F3EDE6] truncate">{{ user?.name }}</p>
                                    <p class="text-xs text-[#6B5C55] dark:text-[#C9B8A6] truncate">{{ user?.email }}</p>
                                </div>

                                <!-- Token balance -->
                                <InertiaLink
                                    href="/tokens"
                                    class="flex items-center justify-between px-4 py-2.5 bg-[#FBF5F0] dark:bg-[#2E2E2B] hover:bg-[#EDE5DD] dark:hover:bg-[#3D3D39] transition"
                                    @click="accountOpen = false"
                                >
                                    <span class="text-xs uppercase tracking-wider font-semibold text-[#6B5C55] dark:text-[#C9B8A6]">Tokens</span>
                                    <span class="text-sm font-bold text-[#C27B5B]">
                                        {{ tokenBalance }}
                                    </span>
                                </InertiaLink>

                                <!-- Menu items -->
                                <div class="py-1">
                                    <InertiaLink
                                        :href="user ? `/${user.username}/account` : '#'"
                                        class="block px-4 py-2 text-sm text-[#3A2520] dark:text-[#F3EDE6] hover:bg-[#FBF5F0] dark:hover:bg-[#2E2E2B] transition"
                                        @click="accountOpen = false"
                                    >
                                        Account settings
                                    </InertiaLink>
                                    <InertiaLink
                                        href="/inbox"
                                        class="flex items-center justify-between px-4 py-2 text-sm text-[#3A2520] dark:text-[#F3EDE6] hover:bg-[#FBF5F0] dark:hover:bg-[#2E2E2B] transition"
                                        @click="accountOpen = false"
                                    >
                                        Notifications
                                        <span v-if="unreadCount > 0" class="text-[10px] font-bold text-white bg-[#C27B5B] rounded-full px-1.5">
                                            {{ unreadCount > 9 ? '9+' : unreadCount }}
                                        </span>
                                    </InertiaLink>
                                    <InertiaLink
                                        href="/support"
                                        class="flex items-center justify-between px-4 py-2 text-sm text-[#3A2520] dark:text-[#F3EDE6] hover:bg-[#FBF5F0] dark:hover:bg-[#2E2E2B] transition"
                                        @click="accountOpen = false"
                                    >
                                        Help &amp; Support
                                        <span v-if="supportUnread > 0" class="text-[10px] font-bold text-white bg-[#C27B5B] rounded-full px-1.5">
                                            {{ supportUnread > 9 ? '9+' : supportUnread }}
                                        </span>
                                    </InertiaLink>
                                    <InertiaLink
                                        v-if="user?.is_admin"
                                        href="/admin"
                                        class="block px-4 py-2 text-sm font-semibold text-[#C27B5B] hover:bg-[#FBF5F0] dark:hover:bg-[#2E2E2B] transition"
                                        @click="accountOpen = false"
                                    >
                                        Admin Dashboard
                                    </InertiaLink>
                                </div>

                                <div class="border-t border-[#EDE5DD] dark:border-[#3D3D39] py-1">
                                    <button
                                        @click="logout"
                                        class="w-full text-left px-4 py-2 text-sm text-[#6B5C55] dark:text-[#C9B8A6] hover:bg-[#FBF5F0] dark:hover:bg-[#2E2E2B] transition"
                                    >
                                        Sign out
                                    </button>
                                </div>
                            </div>
                        </transition>
                    </div>
                </template>

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
                <div class="space-y-1 px-4 pt-3 pb-5">
                    <template v-if="!loggedIn">
                        <InertiaLink href="/signup" class="block py-2 text-base font-medium text-[#3A2520] dark:text-[#EDE5DD] hover:text-[#C27B5B]" @click="mobileOpen = false"> Sign Up </InertiaLink>
                        <InertiaLink href="/login" class="block py-2 text-base font-medium text-[#3A2520] dark:text-[#EDE5DD] hover:text-[#C27B5B]" @click="mobileOpen = false"> Login </InertiaLink>
                    </template>
                    <template v-else>
                        <div class="flex items-center gap-3 py-3 border-b border-[#EDE5DD] dark:border-[#3D3D39] mb-2">
                            <span class="h-9 w-9 rounded-full bg-[#C27B5B] flex items-center justify-center text-white text-sm font-bold">
                                {{ user?.name?.charAt(0)?.toUpperCase() }}
                            </span>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-sm truncate text-[#3A2520] dark:text-[#F3EDE6]">{{ user?.name }}</p>
                                <p class="text-xs text-[#C27B5B] font-semibold">{{ tokenBalance }} tokens</p>
                            </div>
                        </div>
                        <InertiaLink href="/recipes" class="block py-2 text-base font-medium text-[#3A2520] dark:text-[#EDE5DD] hover:text-[#C27B5B]" @click="mobileOpen = false"> Recipes </InertiaLink>
                        <InertiaLink href="/meal-plans" class="block py-2 text-base font-medium text-[#3A2520] dark:text-[#EDE5DD] hover:text-[#C27B5B]" @click="mobileOpen = false"> Meal Plans </InertiaLink>
                        <InertiaLink href="/tokens" class="block py-2 text-base font-medium text-[#3A2520] dark:text-[#EDE5DD] hover:text-[#C27B5B]" @click="mobileOpen = false"> Buy Tokens </InertiaLink>
                        <InertiaLink href="/inbox" class="flex items-center justify-between py-2 text-base font-medium text-[#3A2520] dark:text-[#EDE5DD] hover:text-[#C27B5B]" @click="mobileOpen = false">
                            Notifications
                            <span v-if="unreadCount > 0" class="text-[10px] font-bold text-white bg-[#C27B5B] rounded-full px-1.5">{{ unreadCount > 9 ? '9+' : unreadCount }}</span>
                        </InertiaLink>
                        <InertiaLink href="/support" class="flex items-center justify-between py-2 text-base font-medium text-[#3A2520] dark:text-[#EDE5DD] hover:text-[#C27B5B]" @click="mobileOpen = false">
                            <span>Help &amp; Support</span>
                            <span v-if="supportUnread > 0" class="text-[10px] font-bold text-white bg-[#C27B5B] rounded-full px-1.5">
                                {{ supportUnread > 9 ? '9+' : supportUnread }}
                            </span>
                        </InertiaLink>
                        <InertiaLink :href="user ? `/${user.username}/account` : '#'" class="block py-2 text-base font-medium text-[#3A2520] dark:text-[#EDE5DD] hover:text-[#C27B5B]" @click="mobileOpen = false"> Account </InertiaLink>
                        <InertiaLink v-if="user?.is_admin" href="/admin" class="block py-2 text-base font-semibold text-[#C27B5B] hover:text-[#A8664A]" @click="mobileOpen = false"> Admin Dashboard </InertiaLink>
                        <button @click="logout" class="w-full text-left py-2 text-base font-medium text-[#6B5C55] dark:text-[#C9B8A6]">
                            Sign out
                        </button>
                    </template>
                </div>
            </div>
        </transition>
    </header>
</template>
