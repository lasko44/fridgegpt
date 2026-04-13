<script setup lang="ts">
import Footer from '@/shared/Footer.vue';
import Navigation from '@/shared/Navigation.vue';
import ToastMessage from '@/shared/ToastMessage.vue';
import { showToast, toastType, toastTitle, toastMessage, toastHref } from '@/stores/toastStore';
import type { ToastType } from '@/types/toast';
import { useFlashToast } from '@/composables/useFlashToast';
import { useMealPlanWatcher } from '@/composables/useMealPlanWatcher';
import { useNotificationWatcher } from '@/composables/useNotificationWatcher';
import { router, usePage } from '@inertiajs/vue3';
import { computed, provide } from 'vue';

const loggedIn = false;
useFlashToast();

const page = usePage();
const user = computed(() => (page.props as any).auth?.user ?? null);
const impersonating = computed(() => (page.props as any).auth?.impersonating ?? false);
provide('user', user.value);

function stopImpersonating() {
    router.post('/stop-impersonating');
}

// Background watchers for logged-in users
if (user.value) {
    useMealPlanWatcher();
    useNotificationWatcher('/notifications/feed', `user-${user.value.uuid}`);
}
</script>

<template>
    <div class="flex min-h-screen flex-col">
        <div v-if="impersonating" class="bg-amber-500 text-white px-4 py-2 text-sm flex items-center justify-center gap-3 sticky top-0 z-50">
            <span>👁️ Impersonating <strong>{{ user?.name }}</strong></span>
            <button @click="stopImpersonating" class="px-3 py-1 rounded bg-white/20 hover:bg-white/30 font-semibold text-xs">
                Stop impersonating
            </button>
        </div>
        <Navigation :logged-in="loggedIn" />
        <ToastMessage :type="toastType as ToastType" :title="toastTitle" :message="toastMessage" :show="showToast" :href="toastHref" />
        <main id="main-content" class="flex-1 bg-[#FBF5F0] text-[#3A2520] dark:bg-[#1A1A18] dark:text-[#E8E0D4]" tabindex="-1">
            <slot />
        </main>
        <Footer />
    </div>
</template>
