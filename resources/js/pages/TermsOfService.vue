<script setup lang="ts">
import MyLayout from '../layouts/MyLayout.vue'
import { Link } from '@inertiajs/vue3'
</script>

<template>
    <MyLayout>
        <div class="max-w-2xl mx-auto p-6 bg-white rounded shadow text-gray-900">
            <h1 class="text-3xl font-bold mb-4">Terms of Service</h1>
            <p class="mb-4">
                By using FridgeGPT, you agree to comply with these Terms of Service. Please read them carefully before using our app.
            </p>
            <h2 class="text-xl font-semibold mb-2">Use of Service</h2>
            <p class="mb-4">
                FridgeGPT is provided for personal, non-commercial use only. You must not misuse the service or attempt to access it in unauthorized ways.
            </p>
            <h2 class="text-xl font-semibold mb-2">User Responsibilities</h2>
            <p class="mb-4">
                You are responsible for the accuracy of the information you provide. Do not submit harmful, illegal, or offensive content.
            </p>
            <h2 class="text-xl font-semibold mb-2">Limitation of Liability</h2>
            <p class="mb-4">
                FridgeGPT is provided "as is" without warranties. We are not liable for any damages or losses resulting from your use of the app or the recipes generated.
            </p>
            <h2 class="text-xl font-semibold mb-2">Changes to Terms</h2>
            <p class="mb-4">
                We may update these Terms of Service at any time. Continued use of the app after changes means you accept the new terms.
            </p>
            <p class="mb-8">
                If you have any questions about these terms, please contact us.
            </p>
            <Link href="/" class="text-blue-700 hover:underline">
                &larr; Back to Home
            </Link>
        </div>
    </MyLayout>
</template>