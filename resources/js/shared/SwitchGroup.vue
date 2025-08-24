<script setup lang="ts">
import { ref, watch, toRefs, computed } from 'vue';

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

const { modelValue, columns, single } = toRefs(props);
const selected = ref(props.single ? (modelValue.value as string) : [...(modelValue.value as string[])]);

watch(selected, (val) => emit('update:modelValue', val));
watch(modelValue, (val) => {
    if (single.value) selected.value = val as string;
    else selected.value = [...(val as string[])];
});

const groupClass = computed(() =>
    columns?.value && columns.value > 1
        ? `grid grid-cols-${columns.value} gap-4`
        : 'flex flex-col gap-4'
);
</script>

<template>
    <section>
        <h3 class="mb-4 text-lg font-bold">{{ label }}</h3>
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
                    class="w-11 h-6 bg-gray-300 rounded-full transition-colors relative
          peer-checked:bg-gradient-to-r peer-checked:from-teal-500 peer-checked:via-cyan-500 peer-checked:to-blue-500
          after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full
          after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-5"
                ></span>
                <span>{{ option }}</span>
            </label>
        </div>
    </section>
</template>