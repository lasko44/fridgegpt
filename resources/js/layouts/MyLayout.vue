<script setup lang="ts">
import Footer from '@/shared/Footer.vue';
import Navigation from '@/shared/Navigation.vue';
const loggedIn = false
</script>

<template>
    <div class="min-h-screen flex flex-col">
        <Navigation :logged-in="loggedIn" />
        <main class="flex-1  bg-gray-100 text-gray-800">
            <slot />
        </main>
        <Footer/>
    </div>
</template>