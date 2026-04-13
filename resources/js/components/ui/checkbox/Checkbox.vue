<script setup lang="ts">
const props = defineProps<{ modelValue?: boolean; id?: string; checked?: boolean }>();
const emit = defineEmits<{ (e: 'update:modelValue', v: boolean): void; (e: 'update:checked', v: boolean): void }>();
function toggle() { const v = !(props.checked ?? props.modelValue ?? false); emit('update:modelValue', v); emit('update:checked', v); }
</script>
<template>
    <button type="button" role="checkbox" :aria-checked="checked ?? modelValue ?? false" :id="id" @click="toggle" class="flex h-4 w-4 shrink-0 items-center justify-center rounded border-2 transition" :class="(checked ?? modelValue) ? 'border-[#C27B5B] bg-[#C27B5B]' : 'border-[#EDE5DD] dark:border-[#3D3D39]'">
        <svg v-if="checked ?? modelValue" class="h-3 w-3 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
    </button>
</template>
