<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    open: boolean;
    title: string;
    message?: string;
    confirmLabel?: string;
    cancelLabel?: string;
    variant?: 'default' | 'danger';
    processing?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    confirmLabel: 'Confirm',
    cancelLabel: 'Cancel',
    variant: 'default',
    processing: false,
});

const emit = defineEmits<{
    (e: 'confirm'): void;
    (e: 'cancel'): void;
}>();

const confirmClass = computed(() =>
    props.variant === 'danger'
        ? 'bg-red-600 hover:bg-red-700 text-white'
        : 'bg-[#C27B5B] hover:bg-[#A8664A] text-white'
);
</script>

<template>
    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="open"
                class="fixed inset-0 z-[60] flex items-center justify-center px-4"
                role="dialog"
                aria-modal="true"
            >
                <!-- Backdrop -->
                <div
                    class="absolute inset-0 bg-[#3A2520]/40 backdrop-blur-sm"
                    @click="emit('cancel')"
                />

                <!-- Dialog -->
                <div class="relative w-full max-w-md rounded-2xl bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] shadow-xl overflow-hidden">
                    <div class="px-6 py-5">
                        <h2 class="font-serif text-lg font-bold text-[#3A2520] dark:text-[#F3EDE6]">
                            {{ title }}
                        </h2>
                        <p
                            v-if="message"
                            class="mt-2 text-sm text-[#6B5C55] dark:text-[#C9B8A6] leading-relaxed"
                        >
                            {{ message }}
                        </p>
                    </div>
                    <div class="px-6 py-4 bg-[#FBF5F0] dark:bg-[#2E2E2B] flex justify-end gap-3 border-t border-[#EDE5DD] dark:border-[#3D3D39]">
                        <button
                            type="button"
                            @click="emit('cancel')"
                            :disabled="processing"
                            class="px-4 py-2 rounded-lg border border-[#EDE5DD] dark:border-[#3D3D39] text-sm font-medium text-[#6B5C55] dark:text-[#C9B8A6] hover:bg-white dark:hover:bg-[#3D3D39] transition disabled:opacity-50"
                        >
                            {{ cancelLabel }}
                        </button>
                        <button
                            type="button"
                            @click="emit('confirm')"
                            :disabled="processing"
                            class="px-4 py-2 rounded-lg text-sm font-semibold transition disabled:opacity-50"
                            :class="confirmClass"
                        >
                            {{ processing ? 'Working...' : confirmLabel }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
.modal-enter-active > div:last-child,
.modal-leave-active > div:last-child {
    transition: transform 0.2s ease;
}
.modal-enter-from > div:last-child {
    transform: scale(0.95);
}
.modal-leave-to > div:last-child {
    transform: scale(0.95);
}
</style>
