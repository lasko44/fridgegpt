<script setup lang="ts">
import MyLayout from '@/layouts/MyLayout.vue';
import Profile from '@/shared/AccountComponents/Profile.vue';
import { ref } from 'vue';

const profileSelected = ref(true);
const subscriptionSelected = ref(false);
const deleteSelected = ref(false);

function updateSelection(selection: string) {
    profileSelected.value = selection === 'profile';
    subscriptionSelected.value = selection === 'subscription';
    deleteSelected.value = selection === 'delete';
}

</script>

<template>
    <MyLayout>
        <section class="mx-auto max-w-5xl py-10">
            <div class="flex">
                <div class="w-1/3">
                    <h1 class="text-4xl"> Account Settings</h1>
                    <ul>
                        <li class="my-4">
                            <button
                                @click="updateSelection('profile')"
                                class="text-blue-700 hover:underline hover:cursor-pointer">
                                Edit Profile
                            </button>
                        </li>
                        <li class="my-4">
                            <button
                                @click="updateSelection('subscription')"
                                class="text-blue-700 hover:underline hover:cursor-pointer">
                                Manage Subscription
                            </button>
                        </li>
                        <li class="my-4">
                            <button
                                @click="updateSelection('delete')"
                                class="text-red-800 hover:underline hover:cursor-pointer">
                                Delete Account
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="w-2/3">
                    <Profile v-if="profileSelected"/>

                </div>

            </div>

        </section>
    </MyLayout>
</template>
