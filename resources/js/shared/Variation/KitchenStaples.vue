<script setup lang="ts">
import { computed } from 'vue';
import SwitchGroup from '../SwitchGroup.vue';

const props = defineProps<{
    modelValue: boolean;
}>();
const emit = defineEmits(['update:modelValue']);

const options = ['Include Kitchen Staples'];
const selected = computed({
    get: () => (props.modelValue ? [options[0]] : []),
    set: (val: string[]) => emit('update:modelValue', val.includes(options[0])),
});
</script>

<template>
    <section>
        <SwitchGroup
            :options="options"
            v-model="selected"
            label="Kitchen Staples"
            :single="false"
        />
    </section>
</template>