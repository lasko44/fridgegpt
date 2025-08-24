<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    modelValue: number;
    label?: string;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: number): void;
}>();

const options = computed(() => Array.from({ length: 20 }, (_, i) => i + 1));

function onChange(event: Event) {
    emit('update:modelValue', Number((event.target as HTMLSelectElement).value));
}
</script>

<template>
    <div class="flex flex-col gap-2">
        <label v-if="label" class="font-semibold text-gray-700 mb-1">{{ label }}</label>
        <select
            :value="modelValue"
            @change="onChange"
            class="rounded-lg border border-gray-300 px-4 py-2 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition
        shadow-sm hover:border-cyan-400"
        >
            <option v-for="n in options" :key="n" :value="n">
                {{ n }} {{ n === 1 ? 'person' : 'people' }}
            </option>
        </select>
    </div>
</template>

<style scoped>
/* Add custom styles here if needed */
</style>