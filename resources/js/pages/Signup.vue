<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import MyLayout from '../layouts/MyLayout.vue';

const name = ref('');
const email = ref('');
const password = ref('');

function signUpWithEmail() {
    router.post('/signup', { name: name.value, email: email.value, password: password.value });
}

function signUpWithGoogle() {
    window.location.href = '/auth/google';
}

function signUpWithFacebook() {
    window.location.href = '/auth/facebook';
}
</script>

<template>
    <MyLayout>
        <main class="flex items-center justify-center min-h-[70vh] px-4 py-10">
            <section class="w-full max-w-md rounded-2xl bg-white dark:bg-[#222220] p-8 shadow-sm border border-[#EDE5DD] dark:border-[#3D3D39]" aria-labelledby="signup-heading">
                <h1 id="signup-heading" class="mb-6 text-center text-3xl font-bold font-serif text-[#3A2520] dark:text-[#E8E0D4]">Create your account</h1>

                <form @submit.prevent="signUpWithEmail" class="flex flex-col gap-5" aria-label="Sign up form">
                    <span id="signup-desc" class="sr-only">Create an account by filling out your name, email, and password.</span>
                    <div>
                        <label for="name" class="mb-1.5 block text-sm font-medium text-[#3A2520] dark:text-[#C9B8A6]">Name</label>
                        <input
                            v-model="name"
                            id="name"
                            name="name"
                            type="text"
                            placeholder="Your name"
                            required
                            autocomplete="name"
                            class="w-full rounded-xl border border-[#EDE5DD] dark:border-[#3D3D39] bg-[#FBF5F0] dark:bg-[#2E2E2B] text-[#3A2520] dark:text-[#E8E0D4] placeholder-[#B0A196] px-4 py-3 text-sm focus:ring-2 focus:ring-[#C27B5B] focus:border-transparent focus:outline-none"
                            aria-required="true"
                        />
                    </div>
                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-medium text-[#3A2520] dark:text-[#C9B8A6]">Email</label>
                        <input
                            v-model="email"
                            id="email"
                            name="email"
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
                            name="password"
                            type="password"
                            placeholder="Create a password"
                            required
                            autocomplete="new-password"
                            class="w-full rounded-xl border border-[#EDE5DD] dark:border-[#3D3D39] bg-[#FBF5F0] dark:bg-[#2E2E2B] text-[#3A2520] dark:text-[#E8E0D4] placeholder-[#B0A196] px-4 py-3 text-sm focus:ring-2 focus:ring-[#C27B5B] focus:border-transparent focus:outline-none"
                            aria-required="true"
                        />
                    </div>

                    <button
                        type="submit"
                        class="w-full rounded-xl bg-[#C27B5B] hover:bg-[#A8664A] px-4 py-3 text-base font-semibold text-white transition focus:ring-2 focus:ring-[#C27B5B] focus:outline-none"
                    >
                        Create Account
                    </button>
                </form>

                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-[#EDE5DD] dark:border-[#3D3D39]"></div></div>
                    <div class="relative flex justify-center"><span class="bg-white dark:bg-[#222220] px-3 text-xs text-[#6B5C55] dark:text-[#C9B8A6]">or continue with</span></div>
                </div>

                <div class="flex flex-col gap-3">
                    <button
                        @click="signUpWithGoogle"
                        class="flex items-center justify-center gap-2 w-full rounded-xl border border-[#EDE5DD] dark:border-[#3D3D39] bg-white dark:bg-[#2E2E2B] px-4 py-3 text-sm font-medium text-[#3A2520] dark:text-[#E8E0D4] transition hover:bg-[#FBF5F0] dark:hover:bg-[#3D3D39] focus:ring-2 focus:ring-[#C27B5B] focus:outline-none"
                        aria-label="Sign up with Google"
                    >
                        <svg width="18" height="18" viewBox="0 0 48 48"><path fill="#4285F4" d="M44.5 20H24v8.5h11.7C34.7 32.9 30.1 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.9 1.1 8.1 2.9l6.2-6.2C34.5 6.7 29.5 4.5 24 4.5 13.8 4.5 5.5 12.8 5.5 23S13.8 41.5 24 41.5c10.2 0 18.5-8.3 18.5-18.5 0-1.2-.1-2.3-.3-3.5z"/><path fill="#34A853" d="M6.3 14.7l7 5.1C15.3 17.1 19.3 14.5 24 14.5c3.1 0 5.9 1.1 8.1 2.9l6.2-6.2C34.5 6.7 29.5 4.5 24 4.5c-6.6 0-12 5.4-12 12 0 2.1.5 4.1 1.3 5.8z"/><path fill="#FBBC05" d="M24 41.5c5.1 0 9.7-1.7 13.3-4.7l-6.4-5.2c-2.1 1.4-4.8 2.2-7.9 2.2-6.1 0-11.3-4.1-13.1-9.6l-7 5.4C8.2 37.2 15.5 41.5 24 41.5z"/><path fill="#EA4335" d="M44.5 20H24v8.5h11.7c-1.1 3.1-4.1 5.5-7.7 5.5-6.1 0-11.3-4.1-13.1-9.6l-7 5.4C8.2 37.2 15.5 41.5 24 41.5c10.2 0 18.5-8.3 18.5-18.5 0-1.2-.1-2.3-.3-3.5z"/></svg>
                        Google
                    </button>
                    <button
                        @click="signUpWithFacebook"
                        class="flex items-center justify-center gap-2 w-full rounded-xl border border-[#EDE5DD] dark:border-[#3D3D39] bg-white dark:bg-[#2E2E2B] px-4 py-3 text-sm font-medium text-[#3A2520] dark:text-[#E8E0D4] transition hover:bg-[#FBF5F0] dark:hover:bg-[#3D3D39] focus:ring-2 focus:ring-[#C27B5B] focus:outline-none"
                        aria-label="Sign up with Facebook"
                    >
                        <svg width="18" height="18" viewBox="0 0 48 48"><path fill="#1877F2" d="M24 4C12.95 4 4 12.95 4 24c0 9.95 7.65 18.16 17.44 19.77V30.89h-5.25v-6.89h5.25v-5.25c0-5.19 3.16-8.03 7.78-8.03 2.21 0 4.09.16 4.64.24v5.38h-3.18c-2.5 0-2.98 1.19-2.98 2.93v3.73h6.01l-.78 6.89h-5.23v12.88C40.35 42.16 48 33.95 48 24c0-11.05-8.95-20-20-20z"/></svg>
                        Facebook
                    </button>
                </div>

                <p class="mt-6 text-center text-sm text-[#6B5C55] dark:text-[#C9B8A6]">
                    Already have an account?
                    <a href="/login" class="font-medium text-[#C27B5B] hover:underline">Sign in</a>
                </p>
            </section>
        </main>
    </MyLayout>
</template>
