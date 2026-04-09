<script setup lang="ts">
import { computed } from 'vue';

const restrictions = [
    'Vegan', 'Vegetarian', 'Gluten-Free', 'Dairy-Free', 'Egg-Free', 'Soy-Free',
    'Nut-Free', 'Peanut-Free', 'Halal', 'Kosher', 'Shellfish-Free', 'Low FODMAP',
    'Paleo', 'Pescatarian', 'Low-Sodium', 'Sugar-Free', 'Diabetic-Friendly',
];

const props = defineProps<{
    modelValue: string[];
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string[]): void;
}>();

function toggle(option: string) {
    const current = [...props.modelValue];
    const idx = current.indexOf(option);
    if (idx >= 0) {
        current.splice(idx, 1);
    } else {
        current.push(option);
    }
    emit('update:modelValue', current);
}
</script>

<template>
    <section>
        <h3 class="mb-3 text-lg font-bold text-gray-900 dark:text-white">Dietary Restrictions</h3>
        <div class="flex flex-wrap gap-2" role="group" aria-label="Dietary restrictions">
            <button
                v-for="option in restrictions"
                :key="option"
                type="button"
                @click="toggle(option)"
                class="px-3 py-1.5 text-sm rounded-full border transition-colors focus:outline-none focus:ring-2 focus:ring-[#C27B5B]"
                :class="modelValue.includes(option)
                    ? 'bg-[#C27B5B] border-[#C27B5B] text-white'
                    : 'bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:border-[#C27B5B] hover:text-[#C27B5B] dark:hover:text-[#D4967E]'"
                :aria-pressed="modelValue.includes(option)"
                :aria-label="option"
            >
                {{ option }}
            </button>
        </div>
    </section>
</template>
