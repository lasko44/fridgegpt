<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    options: string[];
    modelValue: string[] | string;
    label: string;
    columns?: number;
    single?: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string[] | string): void;
}>();

const selected = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val),
});

const groupClass = computed(() =>
    props.columns && props.columns > 1
        ? `grid grid-cols-${props.columns} gap-4`
        : 'flex flex-col gap-4'
);
</script>

<template>
    <section>
        <h3 class="mb-4 text-lg font-bold text-gray-900 dark:text-white">{{ label }}</h3>
        <div :class="groupClass">
            <label
                v-for="option in options"
                :key="option"
                class="flex items-center gap-3 cursor-pointer"
            >
                <input
                    v-if="single"
                    type="radio"
                    :value="option"
                    v-model="selected"
                    class="sr-only peer"
                />
                <input
                    v-else
                    type="checkbox"
                    :value="option"
                    v-model="selected"
                    class="sr-only peer"
                />
                <span
                    class="w-11 h-6 bg-gray-300 dark:bg-gray-600 rounded-full transition-colors relative
          peer-checked:bg-[#C27B5B]
          after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full
          after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-5"
                ></span>
                <span class="text-gray-800 dark:text-gray-200">{{ option }}</span>
            </label>
        </div>
    </section>
</template>