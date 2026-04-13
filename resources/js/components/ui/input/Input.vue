<script setup lang="ts">
const props = defineProps<{ modelValue?: string; type?: string; placeholder?: string; id?: string; required?: boolean; disabled?: boolean; autocomplete?: string }>();
const emit = defineEmits<{ (e: 'update:modelValue', v: string): void }>();
</script>
<template>
    <input :value="modelValue" @input="emit('update:modelValue', ($event.target as HTMLInputElement).value)" :type="type||'text'" :placeholder="placeholder" :id="id" :required="required" :disabled="disabled" :autocomplete="autocomplete" class="w-full rounded-xl border border-[#EDE5DD] dark:border-[#3D3D39] bg-[#FBF5F0] dark:bg-[#2E2E2B] text-[#3A2520] dark:text-[#E8E0D4] placeholder-[#B0A196] px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#C27B5B] focus:outline-none" />
</template>
