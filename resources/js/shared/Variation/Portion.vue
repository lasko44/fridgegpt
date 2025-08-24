<script setup lang="ts">
import { ref } from 'vue';
import SwitchGroup from '../SwitchGroup.vue';

const portions = [
    'Single',
    'Double',
    'Triple',
];

const selected = ref<string>(''); // single selection

const emit = defineEmits<{
    (e: 'update:selected', value: string): void;
}>();

function handleUpdate(val: string | string[]) {
    emit('update:selected', typeof val === 'string' ? val : (val[0] ?? ''));
}
</script>

<template>
    <SwitchGroup
        :options="portions"
        v-model="selected"
        label="Portion Size"
        @update:modelValue="handleUpdate"
        :single="true"
    />
</template>