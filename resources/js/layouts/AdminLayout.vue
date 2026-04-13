<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ToastMessage from '@/shared/ToastMessage.vue';
import {
    showToast,
    toastType,
    toastTitle,
    toastMessage,
    toastHref,
} from '@/stores/toastStore';
import { useFlashToast } from '@/composables/useFlashToast';
import { useNotificationWatcher } from '@/composables/useNotificationWatcher';
import type { ToastType } from '@/types/toast';

useFlashToast();

const page = usePage();
const user = computed(() => (page.props as any).auth?.user ?? null);
const supportInboxCount = computed<number>(() => (page.props as any).auth?.admin_support_inbox ?? 0);

// Watch for new support tickets / replies from users
if (user.value?.is_admin) {
    useNotificationWatcher('/admin/notifications/feed', `admin-${user.value.uuid}`);
}

const navItems = computed(() => [
    { label: 'Dashboard', href: '/admin', badge: 0 },
    { label: 'Support Inbox', href: '/admin/support', badge: supportInboxCount.value },
    { label: 'Users', href: '/admin/users', badge: 0 },
    { label: 'Recipes', href: '/admin/recipes', badge: 0 },
    { label: 'Meal Plans', href: '/admin/meal-plans', badge: 0 },
    { label: 'Token Transactions', href: '/admin/tokens/transactions', badge: 0 },
    { label: 'Promo Codes', href: '/admin/tokens/codes', badge: 0 },
    { label: 'Broadcasts', href: '/admin/messages', badge: 0 },
    { label: 'System', href: '/admin/system', badge: 0 },
]);

const sidebarOpen = ref(false);

function isActive(href: string): boolean {
    const url = page.url;
    if (href === '/admin') return url === '/admin' || url === '/admin/';
    return url.startsWith(href);
}
</script>

<template>
    <div class="flex min-h-screen bg-[#FBF5F0] dark:bg-[#1A1A18] text-[#3A2520] dark:text-[#E8E0D4]">
        <ToastMessage
            :type="toastType as ToastType"
            :title="toastTitle"
            :message="toastMessage"
            :show="showToast"
            :href="toastHref"
        />

        <!-- Sidebar -->
        <aside
            class="hidden md:flex md:flex-col w-64 bg-white dark:bg-[#222220] border-r border-[#EDE5DD] dark:border-[#3D3D39] sticky top-0 h-screen"
            aria-label="Admin navigation"
        >
            <div class="px-6 py-5 border-b border-[#EDE5DD] dark:border-[#3D3D39]">
                <Link href="/admin" class="flex items-center gap-2.5">
                    <div class="h-8 w-8 rounded-lg bg-[#C27B5B] flex items-center justify-center">
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2a4 4 0 014-4h4M5 7h14M5 7a2 2 0 100-4m0 4a2 2 0 110-4m14 4v10a2 2 0 01-2 2H7a2 2 0 01-2-2V7" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold font-serif text-[#3A2520] dark:text-[#F3EDE6]">FridgeGPT</p>
                        <p class="text-xs text-[#6B5C55] dark:text-[#C9B8A6]">Admin</p>
                    </div>
                </Link>
            </div>

            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                <Link
                    v-for="item in navItems"
                    :key="item.href"
                    :href="item.href"
                    class="flex items-center justify-between gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition"
                    :class="isActive(item.href)
                        ? 'bg-[#C27B5B]/10 text-[#C27B5B] dark:bg-[#C27B5B]/20'
                        : 'text-[#6B5C55] dark:text-[#C9B8A6] hover:bg-[#FBF5F0] dark:hover:bg-[#2E2E2B] hover:text-[#3A2520] dark:hover:text-[#F3EDE6]'"
                >
                    <span>{{ item.label }}</span>
                    <span
                        v-if="item.badge > 0"
                        class="inline-flex items-center justify-center min-w-5 h-5 px-1.5 rounded-full bg-[#C27B5B] text-white text-[10px] font-bold"
                        :aria-label="`${item.badge} new`"
                    >
                        {{ item.badge > 99 ? '99+' : item.badge }}
                    </span>
                </Link>
            </nav>

            <div class="px-4 py-4 border-t border-[#EDE5DD] dark:border-[#3D3D39] space-y-2">
                <Link
                    href="/"
                    class="flex items-center gap-2 text-xs text-[#6B5C55] dark:text-[#C9B8A6] hover:text-[#C27B5B] transition"
                >
                    &larr; Back to app
                </Link>
                <p v-if="user" class="text-xs text-[#6B5C55] dark:text-[#C9B8A6] truncate">
                    {{ user.name }}
                </p>
            </div>
        </aside>

        <!-- Mobile header -->
        <div class="md:hidden fixed top-0 inset-x-0 bg-white dark:bg-[#222220] border-b border-[#EDE5DD] dark:border-[#3D3D39] z-40">
            <div class="flex items-center justify-between px-4 py-3">
                <Link href="/admin" class="flex items-center gap-2">
                    <div class="h-7 w-7 rounded-md bg-[#C27B5B] flex items-center justify-center">
                        <span class="text-white font-bold text-xs">F</span>
                    </div>
                    <span class="font-semibold text-sm text-[#3A2520] dark:text-[#F3EDE6]">Admin</span>
                </Link>
                <button
                    @click="sidebarOpen = !sidebarOpen"
                    class="relative p-2 rounded-lg text-[#6B5C55] dark:text-[#C9B8A6]"
                    aria-label="Toggle menu"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <span
                        v-if="supportInboxCount > 0"
                        class="absolute -top-0.5 -right-0.5 h-4 min-w-4 px-1 rounded-full bg-[#C27B5B] text-white text-[10px] font-bold flex items-center justify-center"
                    >
                        {{ supportInboxCount > 9 ? '9+' : supportInboxCount }}
                    </span>
                </button>
            </div>
            <nav v-if="sidebarOpen" class="border-t border-[#EDE5DD] dark:border-[#3D3D39] px-4 py-3 space-y-1">
                <Link
                    v-for="item in navItems"
                    :key="item.href"
                    :href="item.href"
                    class="flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium transition"
                    :class="isActive(item.href)
                        ? 'bg-[#C27B5B]/10 text-[#C27B5B]'
                        : 'text-[#6B5C55] dark:text-[#C9B8A6] hover:bg-[#FBF5F0] dark:hover:bg-[#2E2E2B]'"
                    @click="sidebarOpen = false"
                >
                    <span>{{ item.label }}</span>
                    <span
                        v-if="item.badge > 0"
                        class="inline-flex items-center justify-center min-w-5 h-5 px-1.5 rounded-full bg-[#C27B5B] text-white text-[10px] font-bold"
                    >
                        {{ item.badge > 99 ? '99+' : item.badge }}
                    </span>
                </Link>
                <Link href="/" class="block px-3 py-2 text-xs text-[#6B5C55] dark:text-[#C9B8A6]">
                    &larr; Back to app
                </Link>
            </nav>
        </div>

        <!-- Main -->
        <main class="flex-1 min-w-0 mt-14 md:mt-0">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8 py-6 md:py-10">
                <slot />
            </div>
        </main>
    </div>
</template>
