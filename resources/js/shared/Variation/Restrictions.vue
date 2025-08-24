<script setup lang="ts">
import { ref, watch } from 'vue';
import SwitchGroup from '@/shared/SwitchGroup.vue';

const restrictions = [
    'Vegan',
    'Vegetarian',
    'Gluten-Free',
    'Dairy-Free',
    'Egg-Free',
    'Soy-Free',
    'Nut-Free',
    'Peanut-Free',
    'Halal',
    'Kosher',
    'Shellfish-Free',
    'Low FODMAP',
    'Paleo',
    'Pescatarian',
    'Low-Sodium',
    'Sugar-Free',
    'Diabetic-Friendly',
];

const props = defineProps<{
    modelValue: string[];
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string[]): void;
}>();

const selected = ref<string[]>(props.modelValue ?? []);

watch(selected, (val) => {
    emit('update:modelValue', val);
});
watch(() => props.modelValue, (val) => {
    if (val !== selected.value) selected.value = val;
});
</script>

<template>
    <SwitchGroup
        :options="restrictions"
        v-model="selected"
        label="Dietary Restrictions"
        :columns="2"
    >
        <template #default="{ options = [], selected = [], toggle = () => {} }">
            <div class="grid grid-cols-2 gap-4">
                <label
                    v-for="option in options"
                    :key="option"
                    class="flex items-center gap-3 cursor-pointer"
                >
                    <input
                        type="checkbox"
                        :value="option"
                        :checked="selected.includes(option)"
                        @change="toggle(option)"
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
        </template>
    </SwitchGroup>
</template>