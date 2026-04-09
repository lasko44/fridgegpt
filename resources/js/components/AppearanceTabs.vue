<script setup lang="ts">
import { useAppearance } from '@/composables/useAppearance';

const { appearance, updateAppearance } = useAppearance();

const options = [
    { value: 'light', label: 'Light', icon: '☀️' },
    { value: 'dark', label: 'Dark', icon: '🌙' },
    { value: 'system', label: 'System', icon: '💻' },
] as const;
</script>

<template>
    <div class="inline-flex rounded-lg border border-gray-200 dark:border-gray-700 p-1" role="radiogroup" aria-label="Theme selection">
        <button
            v-for="option in options"
            :key="option.value"
            @click="updateAppearance(option.value)"
            :class="[
                'flex items-center gap-2 rounded-md px-4 py-2 text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500',
                appearance === option.value
                    ? 'bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-gray-100'
                    : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800',
            ]"
            :aria-pressed="appearance === option.value"
            :aria-label="option.label + ' theme'"
            role="radio"
        >
            <span aria-hidden="true">{{ option.icon }}</span>
            {{ option.label }}
        </button>
    </div>
</template>
