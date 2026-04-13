<script setup lang="ts">
import { useHead } from '@vueuse/head';
import MyLayout from '@/layouts/MyLayout.vue';
import { Link } from '@inertiajs/vue3';

interface Conversation {
    id: number;
    uuid: string;
    reference: string;
    subject: string;
    status: string;
    last_message_at: string;
    last_message_by_admin: boolean;
}

interface Props {
    conversations: {
        data: Conversation[];
        current_page: number;
        last_page: number;
        total: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
}

defineProps<Props>();

useHead({ title: 'Support | FridgeGPT' });

const statusLabels: Record<string, string> = {
    awaiting_admin: 'Waiting on us',
    awaiting_user: 'Waiting on you',
    resolved: 'Resolved',
    closed: 'Closed',
};

const statusClasses: Record<string, string> = {
    awaiting_admin: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    awaiting_user: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    resolved: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
    closed: 'bg-[#EDE5DD] text-[#6B5C55] dark:bg-[#3D3D39] dark:text-[#C9B8A6]',
};

function formatDate(d: string): string {
    return new Date(d).toLocaleString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}
</script>

<template>
    <MyLayout>
        <div class="mx-auto max-w-3xl px-4 py-10">
            <header class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="font-serif text-3xl font-bold text-[#3A2520] dark:text-[#F3EDE6]">Support</h1>
                    <p class="text-sm text-[#6B5C55] dark:text-[#C9B8A6] mt-1">Get help from our team</p>
                </div>
                <Link
                    href="/support/new"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-[#C27B5B] text-white text-sm font-semibold hover:bg-[#A8664A] transition"
                >
                    + New Conversation
                </Link>
            </header>

            <div v-if="conversations.data.length === 0" class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl p-12 text-center">
                <h2 class="font-serif text-xl font-semibold text-[#3A2520] dark:text-[#F3EDE6] mb-2">No conversations yet</h2>
                <p class="text-sm text-[#6B5C55] dark:text-[#C9B8A6] mb-6">Need help? Start a conversation and we'll get back to you soon.</p>
                <Link
                    href="/support/new"
                    class="inline-flex px-5 py-2.5 rounded-full bg-[#C27B5B] text-white text-sm font-semibold hover:bg-[#A8664A] transition"
                >
                    Contact support
                </Link>
            </div>

            <div v-else class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl overflow-hidden">
                <ul class="divide-y divide-[#EDE5DD] dark:divide-[#3D3D39]">
                    <li v-for="conv in conversations.data" :key="conv.id">
                        <Link
                            :href="`/support/${conv.reference}`"
                            class="flex items-center gap-4 px-5 py-4 hover:bg-[#FBF5F0] dark:hover:bg-[#2E2E2B] transition"
                        >
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <code class="text-[10px] font-mono text-[#C27B5B] font-bold">{{ conv.reference }}</code>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-medium" :class="statusClasses[conv.status]">
                                        {{ statusLabels[conv.status] }}
                                    </span>
                                </div>
                                <p class="font-medium text-sm text-[#3A2520] dark:text-[#F3EDE6] mt-1 truncate">{{ conv.subject }}</p>
                                <p class="text-xs text-[#6B5C55] dark:text-[#C9B8A6] mt-0.5">
                                    {{ conv.last_message_by_admin ? 'Admin replied' : 'You replied' }} &middot; {{ formatDate(conv.last_message_at) }}
                                </p>
                            </div>
                            <svg class="h-5 w-5 text-[#C9B8A6] dark:text-[#6B5C55] shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </Link>
                    </li>
                </ul>
            </div>
        </div>
    </MyLayout>
</template>
