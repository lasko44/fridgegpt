<script setup lang="ts">
import { inject, ref } from 'vue';
import { User } from '@/interfaces/user';
import { route } from 'ziggy-js';
import CancelModal from '@/shared/CancelModal.vue';

const user = inject<User>('user');
const showCancel = ref(false);

function openCancelModal() {
    showCancel.value = true;
}

function handleClose() {
    showCancel.value = false;
}

function handleConfirm() {
    // perform cancel action here (e.g. call API) then close modal
    showCancel.value = false;
}
</script>

<template>
    <section class="my-1 space-y-4 rounded bg-white p-4 shadow">
        <h2 class="mb-4 text-2xl font-bold">Manage Subscription</h2>
        <div class="flex items-center space-x-4" v-if="user?.is_subscribed">
            <p class="mr-4">Payment Method: **** {{ user?.pm_last_four }}</p>
            <div class="flex space-x-2">
                <button class="rounded px-3 py-1 text-blue-600 hover:cursor-pointer hover:text-blue-700 hover:underline">Update</button>
                <button
                    class="rounded px-3 py-1 text-red-600 hover:cursor-pointer hover:text-red-700 hover:underline"
                    @click="openCancelModal"
                >
                    Cancel
                </button>
            </div>
        </div>
        <div v-else>
            <p>You are not currently subscribed to a plan.</p>
            <a
                class="mt-4 inline-block rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700"
                :href="route('subscription.create')">
                Subscribe Now
            </a>
        </div>

        <CancelModal v-if="showCancel" @close="handleClose" @confirm="handleConfirm" />
    </section>
</template>
