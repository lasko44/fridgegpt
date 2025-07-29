vue
<script setup>
const emit = defineEmits(['close']);
</script>

<template>
    <div class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
        <div class="bg-white p-8 rounded shadow text-center w-full max-w-md">
            <h2 class="text-2xl font-semibold mb-2 text-red-600">Oops</h2>
            <p class="mb-6 text-gray-700">Something went wrong. Try reloading the page.</p>
            <button @click="emit('close')" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Close</button>
        </div>
    </div>
</template>