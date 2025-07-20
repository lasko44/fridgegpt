<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import MyLayout from '../layouts/MyLayout.vue';

const email = ref('');
const password = ref('');
const error = ref('');

function loginWithEmail() {
    router.post('/login', { email: email.value, password: password.value }, {
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
        <main>
            <section class="mx-auto my-10 max-w-md rounded bg-white p-8 text-gray-900 shadow" aria-labelledby="login-heading">
                <h1 id="login-heading" class="mb-6 text-center text-3xl font-bold">Login</h1>
                <form @submit.prevent="loginWithEmail" class="mb-8 flex flex-col gap-4" role="form" aria-describedby="login-desc">
                    <span id="login-desc" class="sr-only">Log in to your account with your email and password.</span>
                    <div>
                        <label for="email" class="mb-1 block font-medium text-gray-700">Email</label>
                        <input
                            v-model="email"
                            id="email"
                            name="email"
                            type="email"
                            placeholder="Email"
                            required
                            autocomplete="email"
                            class="w-full rounded border px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:outline-none"
                            aria-required="true"
                        />
                    </div>
                    <div>
                        <label for="password" class="mb-1 block font-medium text-gray-700">Password</label>
                        <input
                            v-model="password"
                            id="password"
                            name="password"
                            type="password"
                            placeholder="Password"
                            required
                            autocomplete="current-password"
                            class="w-full rounded border px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:outline-none"
                            aria-required="true"
                        />
                    </div>
                    <button
                        type="submit"
                        class="rounded-xl hover:cursor-pointer bg-gradient-to-r from-teal-500 to-blue-500 px-4 py-2 text-lg font-bold text-white shadow transition hover:from-teal-600 hover:to-blue-600 focus:ring-2 focus:ring-blue-700 focus:outline-none"
                        aria-label="Login with Email"
                    >
                        Login with Email
                    </button>
                    <p v-if="error" class="text-center text-red-600 mt-2">{{ error }}</p>
                </form>
                <p class="mb-6 text-center" id="social-login-desc">Or login using:</p>
                <div class="flex flex-col gap-4" aria-labelledby="social-login-desc">
                    <button
                        @click="loginWithGoogle"
                        class="flex hover:cursor-pointer items-center justify-center gap-2 rounded border border-[#4285F4] bg-white px-4 py-2 font-bold text-[#4285F4] transition hover:bg-[#f1f3f4] focus:ring-2 focus:ring-[#4285F4] focus:outline-none"
                        aria-label="Login with Google"
                    >
                        <span aria-hidden="true">
                            <svg width="20" height="20" viewBox="0 0 48 48">
                                <g>
                                    <path fill="#4285F4" d="M44.5 20H24v8.5h11.7C34.7 32.9 30.1 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.9 1.1 8.1 2.9l6.2-6.2C34.5 6.7 29.5 4.5 24 4.5 13.8 4.5 5.5 12.8 5.5 23S13.8 41.5 24 41.5c10.2 0 18.5-8.3 18.5-18.5 0-1.2-.1-2.3-.3-3.5z"/>
                                    <path fill="#34A853" d="M6.3 14.7l7 5.1C15.3 17.1 19.3 14.5 24 14.5c3.1 0 5.9 1.1 8.1 2.9l6.2-6.2C34.5 6.7 29.5 4.5 24 4.5c-6.6 0-12 5.4-12 12 0 2.1.5 4.1 1.3 5.8z"/>
                                    <path fill="#FBBC05" d="M24 41.5c5.1 0 9.7-1.7 13.3-4.7l-6.4-5.2c-2.1 1.4-4.8 2.2-7.9 2.2-6.1 0-11.3-4.1-13.1-9.6l-7 5.4C8.2 37.2 15.5 41.5 24 41.5z"/>
                                    <path fill="#EA4335" d="M44.5 20H24v8.5h11.7c-1.1 3.1-4.1 5.5-7.7 5.5-6.1 0-11.3-4.1-13.1-9.6l-7 5.4C8.2 37.2 15.5 41.5 24 41.5c10.2 0 18.5-8.3 18.5-18.5 0-1.2-.1-2.3-.3-3.5z"/>
                                </g>
                            </svg>
                        </span>
                        <span class="sr-only">Login with Google</span>
                        <span aria-hidden="true">Login with Google</span>
                    </button>
                    <button
                        @click="loginWithFacebook"
                        class="flex hover:cursor-pointer items-center justify-center gap-2 rounded bg-[#1877F2] px-4 py-2 font-bold text-white transition hover:bg-[#145db2] focus:ring-2 focus:ring-[#1877F2] focus:outline-none"
                        aria-label="Login with Facebook"
                    >
                        <span aria-hidden="true">
                            <svg width="20" height="20" viewBox="0 0 48 48">
                                <path fill="#1877F2" d="M24 4C12.95 4 4 12.95 4 24c0 9.95 7.65 18.16 17.44 19.77V30.89h-5.25v-6.89h5.25v-5.25c0-5.19 3.16-8.03 7.78-8.03 2.21 0 4.09.16 4.64.24v5.38h-3.18c-2.5 0-2.98 1.19-2.98 2.93v3.73h6.01l-.78 6.89h-5.23v12.88C40.35 42.16 48 33.95 48 24c0-11.05-8.95-20-20-20z"/>
                                <path fill="#FFF" d="M32.22 30.89l.78-6.89h-6.01v-3.73c0-1.74.48-2.93 2.98-2.93h3.18v-5.38c-.55-.08-2.43-.24-4.64-.24-4.62 0-7.78 2.84-7.78 8.03v5.25h-5.25v6.89h5.25v12.88c2.09.33 4.25.33 6.34 0V30.89h5.23z"/>
                            </svg>
                        </span>
                        <span class="sr-only">Login with Facebook</span>
                        <span aria-hidden="true">Login with Facebook</span>
                    </button>
                </div>
            </section>
        </main>
    </MyLayout>
</template>