<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { route } from 'ziggy-js';
import MyLayout from '../layouts/MyLayout.vue';

const page = usePage();

const token = ref((page.props as any).token ?? '');
const email = ref((page.props as any).email ?? '');
const password = ref('');
const passwordConfirmation = ref('');
const error = ref('');
const errors = ref<Record<string, string[]>>({});

function submit() {
    errors.value = {};
    error.value = '';

    router.post(
        route('password.update'),
        {
            token: token.value,
            email: email.value,
            password: password.value,
            password_confirmation: passwordConfirmation.value,
        },
        {
            onSuccess: () => {
                password.value = '';
                passwordConfirmation.value = '';
            },
            onError: (errs) => {
                errors.value = errs as Record<string, string[]>;
                // legacy single error message fallback
                error.value = (errors.value.email?.[0] || errors.value.password?.[0] || '') as string;
            },
        },
    );
}
</script>

<template>
    <MyLayout>
        <main role="main">
            <section class="mx-auto my-10 max-w-md rounded bg-white dark:bg-[#222220] p-8 text-gray-900 dark:text-gray-100 shadow" aria-labelledby="reset-heading">
                <h1 id="reset-heading" class="mb-6 text-center text-3xl font-bold">Reset Password</h1>

                <form @submit.prevent="submit" class="mb-8 flex flex-col gap-4" role="form" aria-describedby="reset-desc">
                    <span id="reset-desc" class="sr-only">Set a new password for your account.</span>

                    <input type="hidden" v-model="token" />

                    <div>
                        <label for="email" class="mb-1 block font-medium text-[#3A2520] dark:text-[#C9B8A6]">Email</label>
                        <input
                            v-model="email"
                            id="email"
                            name="email"
                            type="email"
                            placeholder="Email"
                            required
                            autocomplete="email"
                            class="w-full rounded border border-[#EDE5DD] dark:border-[#3D3D39] bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:outline-none"
                            aria-required="true"
                        />
                        <div v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email[0] }}</div>
                    </div>

                    <div>
                        <label for="password" class="mb-1 block font-medium text-[#3A2520] dark:text-[#C9B8A6]">New password</label>
                        <input
                            v-model="password"
                            id="password"
                            name="password"
                            type="password"
                            placeholder="New password"
                            required
                            autocomplete="new-password"
                            class="w-full rounded border border-[#EDE5DD] dark:border-[#3D3D39] bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:outline-none"
                            aria-required="true"
                        />
                        <div v-if="errors.password" class="mt-1 text-sm text-red-600">{{ errors.password[0] }}</div>
                    </div>

                    <div>
                        <label for="password_confirmation" class="mb-1 block font-medium text-[#3A2520] dark:text-[#C9B8A6]">Confirm password</label>
                        <input
                            v-model="passwordConfirmation"
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            placeholder="Confirm password"
                            required
                            autocomplete="new-password"
                            class="w-full rounded border border-[#EDE5DD] dark:border-[#3D3D39] bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:outline-none"
                            aria-required="true"
                        />
                    </div>

                    <button
                        type="submit"
                        class="rounded-xl bg-gradient-to-r from-teal-500 to-blue-500 px-4 py-2 text-lg font-bold text-white shadow transition hover:cursor-pointer hover:from-teal-600 hover:to-blue-600 focus:ring-2 focus:ring-blue-700 focus:outline-none"
                        aria-label="Reset password"
                    >
                        Set new password
                    </button>

                    <p v-if="error" class="mt-2 text-center text-red-600">{{ error }}</p>

                    <a :href="route('login')" class="text-blue-600 dark:text-blue-400 hover:cursor-pointer hover:text-blue-700 dark:hover:text-blue-300 hover:underline" aria-label="Back to Login"> Back to Login </a>
                </form>
            </section>
        </main>
    </MyLayout>
</template>

<style scoped></style>
