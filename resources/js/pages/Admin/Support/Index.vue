<script setup lang="ts">
import { useHead } from '@vueuse/head';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

function debounce<T extends (...args: any[]) => void>(fn: T, ms: number): T {
    let t: ReturnType<typeof setTimeout> | null = null;
    return ((...args: any[]) => {
        if (t) clearTimeout(t);
        t = setTimeout(() => fn(...args), ms);
    }) as T;
}

interface Conversation {
    id: number;
    uuid: string;
    reference: string;
    subject: string;
    status: string;
    last_message_at: string;
    last_message_by_admin: boolean;
    messages_count: number;
    user: { uuid: string; name: string; email: string } | null;
    messages: { body: string }[];
}

interface Props {
    conversations: {
        data: Conversation[];
        current_page: number;
        last_page: number;
        total: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: { search: string | null; status: string | null };
    counts: {
        all: number;
        awaiting_admin: number;
        awaiting_user: number;
        resolved: number;
        closed: number;
    };
}

const props = defineProps<Props>();
useHead({ title: 'Support | Admin' });

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');

const update = debounce(() => {
    router.get('/admin/support', {
        search: search.value || undefined,
        status: status.value || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true });
}, 300);

watch([search, status], update);

const tabs = [
    { value: '', label: 'All', count: props.counts.all },
    { value: 'awaiting_admin', label: 'Needs reply', count: props.counts.awaiting_admin, accent: true },
    { value: 'awaiting_user', label: 'Awaiting user', count: props.counts.awaiting_user },
    { value: 'resolved', label: 'Resolved', count: props.counts.resolved },
    { value: 'closed', label: 'Closed', count: props.counts.closed },
];

const statusLabels: Record<string, string> = {
    awaiting_admin: 'Needs reply',
    awaiting_user: 'Awaiting user',
    resolved: 'Resolved',
    closed: 'Closed',
};

const statusClasses: Record<string, string> = {
    awaiting_admin: 'bg-amber-100 text-amber-700',
    awaiting_user: 'bg-blue-100 text-blue-700',
    resolved: 'bg-emerald-100 text-emerald-700',
    closed: 'bg-[#EDE5DD] text-[#6B5C55]',
};

function formatDate(d: string): string {
    return new Date(d).toLocaleString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}
</script>

<template>
    <AdminLayout>
        <header class="mb-6">
            <h1 class="font-serif text-3xl font-bold text-[#3A2520] dark:text-[#F3EDE6]">Support</h1>
            <p class="text-sm text-[#6B5C55] dark:text-[#C9B8A6] mt-1">User support conversations</p>
        </header>

        <!-- Status tabs -->
        <div class="flex flex-wrap gap-2 mb-4">
            <button
                v-for="tab in tabs"
                :key="tab.value"
                @click="status = tab.value"
                class="px-4 py-2 rounded-full text-sm font-medium transition border"
                :class="status === tab.value
                    ? 'bg-[#C27B5B] text-white border-[#C27B5B]'
                    : tab.accent && tab.count > 0
                        ? 'bg-amber-50 text-amber-700 border-amber-200 hover:bg-amber-100'
                        : 'bg-white dark:bg-[#222220] text-[#6B5C55] dark:text-[#C9B8A6] border-[#EDE5DD] dark:border-[#3D3D39] hover:border-[#C27B5B]'"
            >
                {{ tab.label }}
                <span class="ml-1 text-xs opacity-75">{{ tab.count }}</span>
            </button>
        </div>

        <!-- Search -->
        <div class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-xl p-4 mb-4">
            <input
                v-model="search"
                type="search"
                placeholder="Search by ticket #, subject, message body, user name or email..."
                class="w-full rounded-lg bg-[#FBF5F0] dark:bg-[#2E2E2B] border border-[#EDE5DD] dark:border-[#3D3D39] px-4 py-2.5 text-sm text-[#3A2520] dark:text-[#F3EDE6] focus:outline-none focus:ring-2 focus:ring-[#C27B5B]"
            />
        </div>

        <!-- Conversations list -->
        <div class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl overflow-hidden">
            <ul class="divide-y divide-[#EDE5DD] dark:divide-[#3D3D39]">
                <li v-for="conv in conversations.data" :key="conv.id">
                    <Link
                        :href="`/admin/support/${conv.reference}`"
                        class="block px-5 py-4 hover:bg-[#FBF5F0] dark:hover:bg-[#2E2E2B] transition"
                    >
                        <div class="flex items-start gap-4">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <code class="text-[10px] font-mono font-bold text-[#C27B5B]">{{ conv.reference }}</code>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-medium" :class="statusClasses[conv.status]">
                                        {{ statusLabels[conv.status] }}
                                    </span>
                                    <span class="text-[10px] text-[#6B5C55] dark:text-[#C9B8A6]">{{ conv.messages_count }} {{ conv.messages_count === 1 ? 'message' : 'messages' }}</span>
                                </div>
                                <p class="font-semibold text-sm text-[#3A2520] dark:text-[#F3EDE6] mt-1.5 truncate">{{ conv.subject }}</p>
                                <p class="text-xs text-[#6B5C55] dark:text-[#C9B8A6] mt-0.5 truncate">
                                    {{ conv.user?.name }} &middot; {{ conv.user?.email }}
                                </p>
                            </div>
                            <span class="text-[10px] text-[#6B5C55] dark:text-[#C9B8A6] shrink-0 mt-1">
                                {{ formatDate(conv.last_message_at) }}
                            </span>
                        </div>
                    </Link>
                </li>
                <li v-if="conversations.data.length === 0" class="px-5 py-12 text-center text-[#6B5C55] dark:text-[#C9B8A6]">
                    No conversations match your filters
                </li>
            </ul>
        </div>

        <!-- Pagination -->
        <div v-if="conversations.last_page > 1" class="mt-4 flex flex-wrap gap-1.5 justify-center">
            <Link
                v-for="link in conversations.links"
                :key="link.label"
                :href="link.url || '#'"
                v-html="link.label"
                class="px-3 py-1.5 rounded-lg text-sm border transition"
                :class="link.active
                    ? 'bg-[#C27B5B] text-white border-[#C27B5B]'
                    : link.url
                        ? 'bg-white dark:bg-[#222220] border-[#EDE5DD] dark:border-[#3D3D39] text-[#6B5C55] dark:text-[#C9B8A6] hover:border-[#C27B5B]'
                        : 'opacity-30 cursor-not-allowed border-[#EDE5DD] dark:border-[#3D3D39]'"
                preserve-scroll
            />
        </div>
    </AdminLayout>
</template>
