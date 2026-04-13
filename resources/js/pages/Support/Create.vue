<script setup lang="ts">
import { useHead } from '@vueuse/head';
import MyLayout from '@/layouts/MyLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';

useHead({ title: 'Contact Support | FridgeGPT' });

const form = useForm({
    subject: '',
    body: '',
});

function submit() {
    if (! form.subject || ! form.body) return;
    form.post('/support');
}
</script>

<template>
    <MyLayout>
        <div class="mx-auto max-w-2xl px-4 py-10">
            <Link href="/support" class="inline-flex items-center gap-1.5 text-sm text-[#6B5C55] dark:text-[#C9B8A6] hover:text-[#C27B5B] mb-4">
                &larr; Back
            </Link>
            <header class="mb-6">
                <h1 class="font-serif text-3xl font-bold text-[#3A2520] dark:text-[#F3EDE6]">New Conversation</h1>
                <p class="text-sm text-[#6B5C55] dark:text-[#C9B8A6] mt-1">Tell us what's going on and we'll get back to you.</p>
            </header>

            <form @submit.prevent="submit" class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl p-6 space-y-5">
                <div>
                    <label class="block text-xs font-medium text-[#6B5C55] dark:text-[#C9B8A6] mb-1.5 uppercase tracking-wider">
                        Subject
                    </label>
                    <input
                        v-model="form.subject"
                        type="text"
                        maxlength="120"
                        required
                        placeholder="e.g. Recipe didn't generate"
                        class="w-full rounded-xl bg-[#FBF5F0] dark:bg-[#2E2E2B] border border-[#EDE5DD] dark:border-[#3D3D39] px-4 py-3 text-sm text-[#3A2520] dark:text-[#F3EDE6] focus:outline-none focus:ring-2 focus:ring-[#C27B5B]"
                    />
                    <p v-if="form.errors.subject" class="text-xs text-red-600 mt-1">{{ form.errors.subject }}</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-[#6B5C55] dark:text-[#C9B8A6] mb-1.5 uppercase tracking-wider">
                        Message
                    </label>
                    <textarea
                        v-model="form.body"
                        required
                        rows="8"
                        maxlength="5000"
                        placeholder="Describe the issue in detail. Include any error messages, what you were trying to do, and what happened instead."
                        class="w-full rounded-xl bg-[#FBF5F0] dark:bg-[#2E2E2B] border border-[#EDE5DD] dark:border-[#3D3D39] px-4 py-3 text-sm text-[#3A2520] dark:text-[#F3EDE6] focus:outline-none focus:ring-2 focus:ring-[#C27B5B]"
                    />
                    <p v-if="form.errors.body" class="text-xs text-red-600 mt-1">{{ form.errors.body }}</p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <Link href="/support" class="px-5 py-2.5 text-sm font-medium text-[#6B5C55] dark:text-[#C9B8A6] hover:text-[#C27B5B]">
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing || !form.subject || !form.body"
                        class="px-6 py-2.5 rounded-full bg-[#C27B5B] text-white text-sm font-semibold hover:bg-[#A8664A] disabled:opacity-50 transition"
                    >
                        {{ form.processing ? 'Sending...' : 'Send Message' }}
                    </button>
                </div>
            </form>
        </div>
    </MyLayout>
</template>
