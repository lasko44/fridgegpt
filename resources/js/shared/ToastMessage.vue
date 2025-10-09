<script setup lang="ts">
import { computed } from 'vue';

type ToastType = 'error' | 'success' | 'info';

interface Props {
    type?: ToastType;
    title?: string;
    message?: string;
    show?: boolean;
}

const props = defineProps<Props>();

const bgClass = computed(() =>
    ({
        error: 'bg-gradient-to-r from-red-100 to-red-200 text-red-900',
        success: 'bg-gradient-to-r from-green-100 to-green-200 text-green-900',
        info: 'bg-gradient-to-r from-blue-100 to-blue-200 text-blue-900',
    })[props.type ?? 'info']
);

const computedTitle = computed(() => props.title ?? {
    error: 'Error',
    success: 'Success',
    info: 'Information',
}[props.type ?? 'info']);

const computedMessage = computed(() => props.message ?? {
    error: 'Something went wrong.',
    success: 'Operation successful.',
    info: 'Here is some information.',
}[props.type ?? 'info']);
</script>

<template>
    <transition name="toast-slide-right">
        <div
            v-if="props.show"
            class="toast-message fixed z-50 mb-4 max-w-sm min-w-[200px] rounded-lg p-4 shadow"
            :class="bgClass"
            :style="{
                right: '2rem',
                top: '2rem',
            }"
        >
            <div class="mb-2 font-bold">{{ computedTitle }}</div>
            <div class="break-words">{{ computedMessage }}</div>
        </div>
    </transition>
</template>

<style scoped>
.toast-slide-right-enter-from {
    transform: translateX(100%);
    opacity: 0;
}
.toast-slide-right-enter-active {
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s;
}
.toast-slide-right-enter-to {
    transform: translateX(0);
    opacity: 1;
}
.toast-message {
    will-change: transform, opacity;
}
</style>

