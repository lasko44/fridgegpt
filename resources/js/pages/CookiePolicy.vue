<script setup lang="ts">
import MyLayout from '../layouts/MyLayout.vue'
import { Link } from '@inertiajs/vue3'
</script>

<template>
    <MyLayout>
        <div class="max-w-2xl mx-auto p-6 bg-white rounded shadow text-gray-900">
            <h1 class="text-3xl font-bold mb-4">Cookie Policy</h1>
            <p class="mb-4">
                FridgeGPT uses cookies to enhance your experience and ensure the proper functioning of our app. By using our service, you consent to our use of cookies as described in this policy.
            </p>
            <h2 class="text-xl font-semibold mb-2">What Are Cookies?</h2>
            <p class="mb-4">
                Cookies are small text files stored on your device by your browser. They help us remember your preferences and improve site performance.
            </p>
            <h2 class="text-xl font-semibold mb-2">How We Use Cookies</h2>
            <p class="mb-4">
                We use cookies for essential functions, analytics, and to remember your settings. We do not use cookies to track you across other sites or share cookie data with third parties.
            </p>
            <h2 class="text-xl font-semibold mb-2">Your Choices</h2>
            <p class="mb-4">
                You can manage or disable cookies in your browser settings. Please note that disabling cookies may affect the functionality of FridgeGPT.
            </p>
            <p class="mb-8">
                If you have any questions about our cookie policy, please contact us.
            </p>
            <Link href="/" class="text-blue-700 hover:underline">
                &larr; Back to Home
            </Link>
        </div>
    </MyLayout>
</template>