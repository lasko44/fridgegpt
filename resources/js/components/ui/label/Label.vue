<script setup lang="ts">
defineProps<{ for?: string }>();
</script>
<template>
    <label :for="$props.for" class="block text-sm font-medium text-[#3A2520] dark:text-[#C9B8A6]"><slot /></label>
</template>
