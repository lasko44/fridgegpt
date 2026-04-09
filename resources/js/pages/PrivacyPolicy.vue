<script setup lang="ts">
import MyLayout from '../layouts/MyLayout.vue'
import { Link } from '@inertiajs/vue3'
</script>

<template>
    <MyLayout>
        <div class="max-w-2xl mx-auto p-6 bg-white dark:bg-[#222220] rounded shadow text-gray-900 dark:text-gray-100" role="main" aria-label="Privacy Policy">
            <h1 class="text-3xl font-bold mb-4">Privacy Policy</h1>
            <p class="mb-4">
                Your privacy is important to us. We do not store your personal data or share it with third parties. All information provided is used solely for generating recipes.
            </p>
            <p class="mb-8">
                If you have any questions about our privacy practices, please contact us.
            </p>
            <Link href="/" class="text-blue-700 dark:text-blue-400 hover:underline" aria-label="Back to Home">
                &larr; Back to Home
            </Link>
        </div>
    </MyLayout>
</template>