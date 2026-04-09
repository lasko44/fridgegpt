```vue
<script setup lang="ts">
import MyLayout from '@/layouts/MyLayout.vue';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import {route} from 'ziggy-js';
import { usePage } from '@inertiajs/vue3';

const email = ref('');
const errors = ref<Record<string, string[]>>({});

const page = usePage();
// initialize the flash-toast composable so it can read `page.props.flash` and display toasts

function submit() {
  errors.value = {};
  router.post(route('forgot-password.store'), { email: email.value });
}
</script>

<template>
  <MyLayout>
    <div class="max-w-md mx-auto p-4" role="main" aria-label="Forgot password">
      <h1 class="text-2xl font-bold mb-4 text-gray-900 dark:text-gray-100">Forgot password</h1>

      <form @submit.prevent="submit" class="space-y-3" aria-label="Forgot password form">
        <div>
          <label for="forgot-email" class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">Email</label>
          <input
            v-model="email"
            id="forgot-email"
            type="email"
            required
            class="w-full border border-gray-300 dark:border-gray-600 rounded px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-teal-500 focus:outline-none"
            placeholder="you@example.com"
            aria-required="true"
          />
          <div v-if="errors.email" class="text-red-600 dark:text-red-400 text-sm mt-1" role="alert">
            {{ errors.email[0] }}
          </div>
        </div>

        <button type="submit" class="bg-blue-600 dark:bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-700 dark:hover:bg-blue-400 focus:ring-2 focus:ring-blue-700 focus:outline-none" aria-label="Send reset link">
          Send reset link
        </button>
      </form>
    </div>
  </MyLayout>
</template>

<style scoped></style>