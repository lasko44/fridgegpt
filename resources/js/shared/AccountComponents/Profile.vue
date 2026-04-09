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
    <section class="rounded-2xl bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] p-6 shadow-sm">
        <form @submit.prevent="updateProfile" class="space-y-5">
            <div>
                <label for="name" class="mb-1.5 block text-sm font-medium text-[#3A2520] dark:text-[#C9B8A6]">Name</label>
                <input
                    v-model="form.name"
                    id="name"
                    name="name"
                    type="text"
                    placeholder="Your name"
                    required
                    autocomplete="name"
                    class="w-full rounded-xl border border-[#EDE5DD] dark:border-[#3D3D39] bg-[#FBF5F0] dark:bg-[#2E2E2B] text-[#3A2520] dark:text-[#E8E0D4] placeholder-[#B0A196] px-4 py-3 text-sm focus:ring-2 focus:ring-[#C27B5B] focus:border-transparent focus:outline-none"
                    aria-required="true"
                />
                <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
            </div>

            <div>
                <label for="email" class="mb-1.5 block text-sm font-medium text-[#3A2520] dark:text-[#C9B8A6]">Email</label>
                <input
                    v-model="form.email"
                    id="email"
                    name="email"
                    type="email"
                    placeholder="you@example.com"
                    required
                    autocomplete="email"
                    class="w-full rounded-xl border border-[#EDE5DD] dark:border-[#3D3D39] bg-[#FBF5F0] dark:bg-[#2E2E2B] text-[#3A2520] dark:text-[#E8E0D4] placeholder-[#B0A196] px-4 py-3 text-sm focus:ring-2 focus:ring-[#C27B5B] focus:border-transparent focus:outline-none"
                    aria-required="true"
                />
                <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
            </div>

            <div>
                <button
                    type="submit"
                    :disabled="form.processing || !hasChanges"
                    class="rounded-xl bg-[#C27B5B] hover:bg-[#A8664A] px-6 py-3 text-sm font-semibold text-white transition hover:cursor-pointer focus:ring-2 focus:ring-[#C27B5B] focus:outline-none disabled:opacity-50"
                >
                    <span v-if="form.processing">Saving...</span>
                    <span v-else>Save</span>
                </button>
            </div>
        </form>
    </section>
</template>
