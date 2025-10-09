<script setup lang="ts">
import Footer from '@/shared/Footer.vue';
import Navigation from '@/shared/Navigation.vue';
import ToastMessage from '@/shared/ToastMessage.vue';
import { showToast, toastType, toastTitle, toastMessage } from '@/stores/toastStore';
import type { ToastType } from '@/types/toast';

const loggedIn = false;

</script>

<template>
    <div class="min-h-screen flex flex-col">
        <Navigation :logged-in="loggedIn" />
        <ToastMessage
            :type="toastType as ToastType"
            :title="toastTitle"
            :message="toastMessage"
            :show="showToast"
        />
        <main class="flex-1 bg-gray-100 text-gray-800">
            <slot />
        </main>
        <Footer/>
    </div>
</template>
