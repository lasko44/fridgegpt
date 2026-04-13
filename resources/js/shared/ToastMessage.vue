<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { dismissToast } from '@/stores/toastStore';

type ToastType = 'error' | 'success' | 'info';

interface Props {
    type?: ToastType;
    title?: string;
    message?: string;
    show?: boolean;
    href?: string | null;
}

const props = defineProps<Props>();

const accentClass = computed(() =>
    ({
        error: 'border-l-red-500',
        success: 'border-l-[#7A8C5A]',
        info: 'border-l-[#C27B5B]',
    })[props.type ?? 'info']
);

const iconClass = computed(() =>
    ({
        error: 'text-red-500',
        success: 'text-[#7A8C5A]',
        info: 'text-[#C27B5B]',
    })[props.type ?? 'info']
);

const computedTitle = computed(() => props.title ?? {
    error: 'Something went wrong',
    success: 'All set',
    info: 'Heads up',
}[props.type ?? 'info']);

const computedMessage = computed(() => props.message ?? '');

function handleClick() {
    if (props.href) {
        router.visit(props.href);
        dismissToast();
    }
}

function handleDismiss(event: Event) {
    event.stopPropagation();
    dismissToast();
}
</script>

<template>
    <transition name="toast-slide-right">
        <div
            v-if="props.show"
            class="toast-message fixed z-50 max-w-sm min-w-[280px] rounded-xl border border-l-4 shadow-lg overflow-hidden bg-white dark:bg-[#222220] border-[#EDE5DD] dark:border-[#3D3D39]"
            :class="[accentClass, { 'cursor-pointer hover:shadow-xl transition-shadow': !!href }]"
            :style="{
                right: '1.5rem',
                top: '1.5rem',
            }"
            role="status"
            aria-live="polite"
            @click="handleClick"
        >
            <div class="flex items-start gap-3 p-4">
                <div class="flex-shrink-0 mt-0.5" :class="iconClass">
                    <svg
                        v-if="props.type === 'success'"
                        class="h-5 w-5"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <svg
                        v-else-if="props.type === 'error'"
                        class="h-5 w-5"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    <svg
                        v-else
                        class="h-5 w-5"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-sm font-serif text-[#3A2520] dark:text-[#F3EDE6]">
                        {{ computedTitle }}
                    </p>
                    <p
                        v-if="computedMessage"
                        class="mt-1 text-xs leading-relaxed text-[#6B5C55] dark:text-[#C9B8A6]"
                    >
                        {{ computedMessage }}
                    </p>
                    <p
                        v-if="href"
                        class="mt-2 text-xs font-semibold text-[#C27B5B]"
                    >
                        Tap to view &rarr;
                    </p>
                </div>
                <button
                    type="button"
                    @click="handleDismiss"
                    class="flex-shrink-0 text-[#6B5C55] dark:text-[#C9B8A6] hover:text-[#3A2520] dark:hover:text-[#F3EDE6] transition"
                    aria-label="Dismiss notification"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </transition>
</template>

<style scoped>
.toast-slide-right-enter-from,
.toast-slide-right-leave-to {
    transform: translateX(120%);
    opacity: 0;
}
.toast-slide-right-enter-active,
.toast-slide-right-leave-active {
    transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s;
}
.toast-slide-right-enter-to,
.toast-slide-right-leave-from {
    transform: translateX(0);
    opacity: 1;
}
.toast-message {
    will-change: transform, opacity;
}
</style>
