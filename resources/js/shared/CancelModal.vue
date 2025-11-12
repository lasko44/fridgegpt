<script setup lang="ts">
import { inject } from 'vue';
import { User } from '@/interfaces/user';
import axios from 'axios';

const user = inject<User>('user');

function unsubscribe() {
    axios.delete(route('subscription.destroy', user!.uuid))
        .then(() => {
            window.location.reload();
        })
        .catch((error: any) => {
            console.error(error);
            window.location.reload();
        });
}
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
        <div class="w-11/12 max-w-md rounded-lg bg-white p-6 shadow-lg">
            <h2 class="mb-4 text-xl font-semibold">Cancel Subscription</h2>
            <p class="mb-6">Are you sure you want to cancel? We will really miss you. 💔</p>
            <div class="flex justify-end space-x-4">
                <button class="rounded bg-gray-300 px-4 py-2 hover:bg-gray-400 hover:cursor-pointer" @click="$emit('close')">No, Go Back</button>
                <button class="rounded bg-red-500 px-4 py-2 text-white hover:bg-red-600 hover:cursor-pointer" @click="unsubscribe">Yes, Cancel</button>
            </div>
        </div>
    </div>
</template>

<style scoped></style>
