<script setup lang="ts">
import { computed, reactive, inject } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { User } from '@/interfaces/user';

const user = inject<User | null>('user');

const initial = reactive({
    name: user?.name ?? '',
    username: user?.username ?? '',
    email: user?.email ?? '',
});

const form = useForm({
    name: initial.name,
    username: initial.username,
    email: initial.email,
});

const hasChanges = computed(() => {
    const trim = (s?: string) => (s ?? '').trim();
    return (
        trim(form.name) !== trim(initial.name) ||
        trim(form.username) !== trim(initial.username) ||
        trim(form.email) !== trim(initial.email)
    );
});

function updateProfile() {
    if (!hasChanges.value || form.processing) return;
    form.put(route('user.update', user?.username));
}
</script>

<template>
    <section class="bg-white p-4 rounded">
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
                <button
                    type="submit"
                    :disabled="form.processing || !hasChanges"
                    class="rounded bg-blue-600 hover:bg-blue-700 px-4 py-2 text-white hover:cursor-pointer disabled:opacity-50"
                >
                    <span v-if="form.processing">Saving...</span>
                    <span v-else>Save</span>
                </button>
            </div>
        </form>
    </section>
</template>
