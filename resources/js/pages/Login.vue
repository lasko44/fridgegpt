<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import MyLayout from '../layouts/MyLayout.vue';
import { route } from 'ziggy-js';

const email = ref('');
const password = ref('');
const remember = ref(false);
const error = ref('');

function loginWithEmail() {
    router.post('/login', { email: email.value, password: password.value, remember: remember.value }, {
        onError: (errors) => {
            error.value = errors.email || errors.password || 'Login failed.';
        }
    });
}

function loginWithGoogle() {
    window.location.href = '/auth/google';
}

function loginWithFacebook() {
    window.location.href = '/auth/facebook';
}
</script>

<template>
    <MyLayout>
        <main class="flex items-center justify-center min-h-[70vh] px-4 py-10">
            <section class="w-full max-w-md rounded-2xl bg-white dark:bg-[#222220] p-8 shadow-sm border border-[#EDE5DD] dark:border-[#3D3D39]" aria-labelledby="login-heading">
                <h1 id="login-heading" class="mb-6 text-center text-3xl font-bold font-serif text-[#3A2520] dark:text-[#E8E0D4]">Welcome back</h1>

                <form @submit.prevent="loginWithEmail" class="flex flex-col gap-5" aria-label="Login form">
                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-medium text-[#3A2520] dark:text-[#C9B8A6]">Email</label>
                        <input
                            v-model="email"
                            id="email"
                            type="email"
                            placeholder="you@example.com"
                            required
                            autocomplete="email"
                            class="w-full rounded-xl border border-[#EDE5DD] dark:border-[#3D3D39] bg-[#FBF5F0] dark:bg-[#2E2E2B] text-[#3A2520] dark:text-[#E8E0D4] placeholder-[#B0A196] px-4 py-3 text-sm focus:ring-2 focus:ring-[#C27B5B] focus:border-transparent focus:outline-none"
                            aria-required="true"
                        />
                    </div>
                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-medium text-[#3A2520] dark:text-[#C9B8A6]">Password</label>
                        <input
                            v-model="password"
                            id="password"
                            type="password"
                            placeholder="Your password"
                            required
                            autocomplete="current-password"
                            class="w-full rounded-xl border border-[#EDE5DD] dark:border-[#3D3D39] bg-[#FBF5F0] dark:bg-[#2E2E2B] text-[#3A2520] dark:text-[#E8E0D4] placeholder-[#B0A196] px-4 py-3 text-sm focus:ring-2 focus:ring-[#C27B5B] focus:border-transparent focus:outline-none"
                            aria-required="true"
                        />
                    </div>
                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="remember"
                                class="h-4 w-4 rounded border-[#EDE5DD] text-[#C27B5B] focus:ring-[#C27B5B]"
                            />
                            <span class="text-sm text-[#6B5C55] dark:text-[#C9B8A6]">Remember me</span>
                        </label>
                        <a :href="route('forgot-password.index')" class="text-sm text-[#C27B5B] hover:underline" aria-label="Forgot Password">
                            Forgot password?
                        </a>
                    </div>

                    <p v-if="error" class="text-sm text-center text-red-600 dark:text-red-400" role="alert">{{ error }}</p>

                    <button
                        type="submit"
                        class="w-full rounded-xl bg-[#C27B5B] hover:bg-[#A8664A] px-4 py-3 text-base font-semibold text-white transition focus:ring-2 focus:ring-[#C27B5B] focus:outline-none"
                    >
                        Sign In
                    </button>
                </form>

                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-[#EDE5DD] dark:border-[#3D3D39]"></div></div>
                    <div class="relative flex justify-center"><span class="bg-white dark:bg-[#222220] px-3 text-xs text-[#6B5C55] dark:text-[#C9B8A6]">or continue with</span></div>
                </div>

                <div class="flex flex-col gap-3">
                    <button
                        @click="loginWithGoogle"
                        class="flex items-center justify-center gap-2 w-full rounded-xl border border-[#EDE5DD] dark:border-[#3D3D39] bg-white dark:bg-[#2E2E2B] px-4 py-3 text-sm font-medium text-[#3A2520] dark:text-[#E8E0D4] transition hover:bg-[#FBF5F0] dark:hover:bg-[#3D3D39] focus:ring-2 focus:ring-[#C27B5B] focus:outline-none"
                    >
                        <svg width="18" height="18" viewBox="0 0 48 48"><path fill="#4285F4" d="M44.5 20H24v8.5h11.7C34.7 32.9 30.1 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.9 1.1 8.1 2.9l6.2-6.2C34.5 6.7 29.5 4.5 24 4.5 13.8 4.5 5.5 12.8 5.5 23S13.8 41.5 24 41.5c10.2 0 18.5-8.3 18.5-18.5 0-1.2-.1-2.3-.3-3.5z"/><path fill="#34A853" d="M6.3 14.7l7 5.1C15.3 17.1 19.3 14.5 24 14.5c3.1 0 5.9 1.1 8.1 2.9l6.2-6.2C34.5 6.7 29.5 4.5 24 4.5c-6.6 0-12 5.4-12 12 0 2.1.5 4.1 1.3 5.8z"/><path fill="#FBBC05" d="M24 41.5c5.1 0 9.7-1.7 13.3-4.7l-6.4-5.2c-2.1 1.4-4.8 2.2-7.9 2.2-6.1 0-11.3-4.1-13.1-9.6l-7 5.4C8.2 37.2 15.5 41.5 24 41.5z"/><path fill="#EA4335" d="M44.5 20H24v8.5h11.7c-1.1 3.1-4.1 5.5-7.7 5.5-6.1 0-11.3-4.1-13.1-9.6l-7 5.4C8.2 37.2 15.5 41.5 24 41.5c10.2 0 18.5-8.3 18.5-18.5 0-1.2-.1-2.3-.3-3.5z"/></svg>
                        Google
                    </button>
                    <button
                        @click="loginWithFacebook"
                        class="flex items-center justify-center gap-2 w-full rounded-xl border border-[#EDE5DD] dark:border-[#3D3D39] bg-white dark:bg-[#2E2E2B] px-4 py-3 text-sm font-medium text-[#3A2520] dark:text-[#E8E0D4] transition hover:bg-[#FBF5F0] dark:hover:bg-[#3D3D39] focus:ring-2 focus:ring-[#C27B5B] focus:outline-none"
                    >
                        <svg width="18" height="18" viewBox="0 0 48 48"><path fill="#1877F2" d="M24 4C12.95 4 4 12.95 4 24c0 9.95 7.65 18.16 17.44 19.77V30.89h-5.25v-6.89h5.25v-5.25c0-5.19 3.16-8.03 7.78-8.03 2.21 0 4.09.16 4.64.24v5.38h-3.18c-2.5 0-2.98 1.19-2.98 2.93v3.73h6.01l-.78 6.89h-5.23v12.88C40.35 42.16 48 33.95 48 24c0-11.05-8.95-20-20-20z"/></svg>
                        Facebook
                    </button>
                </div>

                <p class="mt-6 text-center text-sm text-[#6B5C55] dark:text-[#C9B8A6]">
                    Don't have an account?
                    <a href="/signup" class="font-medium text-[#C27B5B] hover:underline">Sign up</a>
                </p>
            </section>
        </main>
    </MyLayout>
</template>
