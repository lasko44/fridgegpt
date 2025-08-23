<script setup lang="ts">
import { ref } from 'vue';
import SwitchGroup from '../SwitchGroup.vue';

const portions = [
    'Single',
    'Double',
    'Family',
];

const selected = ref<string[]>([]);

const emit = defineEmits<{
    (e: 'update:selected', value: string[]): void;
}>();
</script>

<template>
    <SwitchGroup
        :options="portions"
        v-model="selected"
        label="Portion Size"
        @update:modelValue="emit('update:selected', $event)"
    />
</template>