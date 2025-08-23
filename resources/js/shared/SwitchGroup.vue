<script setup lang="ts">
import { ref, watch, toRefs } from 'vue';

const props = defineProps<{
    options: string[];
    modelValue: string[];
    label: string;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string[]): void;
}>();

const { modelValue } = toRefs(props);
const selected = ref<string[]>([...modelValue.value]);

watch(selected, (val) => emit('update:modelValue', val));
watch(modelValue, (val) => (selected.value = [...val]));
</script>

<template>
    <section>
        <h3 class="mb-4 text-lg font-bold">{{ label }}</h3>
        <div class="flex flex-col gap-4">
            <label
                v-for="option in options"
                :key="option"
                class="flex items-center gap-3 cursor-pointer"
            >
                <input
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

<style scoped>
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0,0,0,0);
    white-space: nowrap;
    border-width: 0;
}
</style>