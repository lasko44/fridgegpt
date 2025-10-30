<script setup lang="ts">
import { useForm } from '@inertiajs/inertia-vue3';

// get the provided user
import { inject } from 'vue';
import { User } from '@/types';
const user = inject<User | null>('user');

console.log(user);

const form = useForm({
    name: user?.name ?? '',
    email: user?.email ?? '',
    password: '',
    password_confirmation: '',
});

function updateProfile() {
    form.post('/user/profile', {
        preserveState: true,
    });
}
</script>

<template>
    <section class="my-4">
        <form @submit.prevent="updateProfile" class="space-y-4">
            <div>
                <label class="block text-sm font-medium">Name</label>
                <input
                    v-model="form.name"
                    id="name"
                    name="name"
                    type="text"
                    placeholder="Name"
                    required
                    autocomplete="name"
                    class="w-full rounded border px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:outline-none"
                    aria-required="true"
                />
                <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium">Email</label>
                <input
                    v-model="form.email"
                    id="email"
                    name="email"
                    type="email"
                    placeholder="Email"
                    required
                    autocomplete="email"
                    class="w-full rounded border px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:outline-none"
                    aria-required="true"
                />
                <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium">New password</label>
                <input
                    v-model="form.password"
                    id="password"
                    name="password"
                    type="password"
                    required
                    autocomplete="password"
                    class="w-full rounded border px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:outline-none"
                    aria-required="true"
                />
                <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium">Confirm password</label>
                <input
                    v-model="form.password_confirmation"
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    required
                    autocomplete="password_confirmation"
                    class="w-full rounded border px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:outline-none"
                    aria-required="true"
                />
            </div>

            <div>
                <button type="submit" :disabled="form.processing" class="rounded bg-blue-600 px-4 py-2 text-white">
                    <span v-if="form.processing">Saving...</span>
                    <span v-else>Save</span>
                </button>
            </div>
        </form>
    </section>
</template>