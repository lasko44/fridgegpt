<script setup lang="ts">
import { useHead } from '@vueuse/head';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import ConfirmDialog from '@/shared/ConfirmDialog.vue';

function debounce<T extends (...args: any[]) => void>(fn: T, ms: number): T {
    let t: ReturnType<typeof setTimeout> | null = null;
    return ((...args: any[]) => {
        if (t) clearTimeout(t);
        t = setTimeout(() => fn(...args), ms);
    }) as T;
}

interface Message {
    id: number;
    uuid: string;
    title: string;
    body: string;
    sent_push: boolean;
    recipients_count: number;
    created_at: string;
    sender: { name: string } | null;
    recipient: { uuid: string; name: string; email: string } | null;
}

interface Props {
    messages: {
        data: Message[];
        current_page: number;
        last_page: number;
        total: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: { search: string | null };
}

const props = defineProps<Props>();

useHead({ title: 'Broadcasts | Admin' });

const search = ref(props.filters?.search ?? '');
const updateSearch = debounce(() => {
    router.get('/admin/messages', {
        search: search.value || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true });
}, 300);
watch(search, updateSearch);

const form = useForm({
    title: '',
    body: '',
    recipient_uuid: '' as string,
    send_push: true,
});

const showConfirm = ref(false);
const confirmDialog = computed(() => ({
    title: form.recipient_uuid ? 'Send message?' : 'Send to all users?',
    message: form.recipient_uuid
        ? 'This will be delivered to the selected user.'
        : 'This message will be sent to every user in the database. This cannot be undone.',
    confirmLabel: form.recipient_uuid ? 'Send' : 'Broadcast to all',
    variant: form.recipient_uuid ? ('default' as const) : ('danger' as const),
}));

function tryToSend() {
    if (! form.title || ! form.body) return;
    showConfirm.value = true;
}

function confirmSend() {
    showConfirm.value = false;
    form.transform((data) => ({
        ...data,
        recipient_uuid: data.recipient_uuid || null,
    }));
    form.post('/admin/messages', {
        preserveScroll: true,
        onSuccess: () => form.reset('title', 'body', 'recipient_uuid'),
    });
}

function formatDate(d: string): string {
    return new Date(d).toLocaleString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}
</script>

<template>
    <AdminLayout>
        <ConfirmDialog
            :open="showConfirm"
            :title="confirmDialog.title"
            :message="confirmDialog.message"
            :confirm-label="confirmDialog.confirmLabel"
            :variant="confirmDialog.variant"
            @confirm="confirmSend"
            @cancel="showConfirm = false"
        />
        <header class="mb-6">
            <h1 class="font-serif text-3xl font-bold text-[#3A2520] dark:text-[#F3EDE6]">Broadcasts</h1>
            <p class="text-sm text-[#6B5C55] dark:text-[#C9B8A6] mt-1">Send announcements or direct messages to users</p>
        </header>

        <div class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-xl p-4 mb-4">
            <input
                v-model="search"
                type="search"
                placeholder="Search by title, body, or recipient..."
                class="w-full rounded-lg bg-[#FBF5F0] dark:bg-[#2E2E2B] border border-[#EDE5DD] dark:border-[#3D3D39] px-4 py-2.5 text-sm text-[#3A2520] dark:text-[#F3EDE6] focus:outline-none focus:ring-2 focus:ring-[#C27B5B]"
            />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Compose -->
            <aside class="lg:col-span-1">
                <div class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl p-5 sticky top-6">
                    <h2 class="font-serif text-base font-semibold mb-4 text-[#3A2520] dark:text-[#F3EDE6]">Compose</h2>
                    <form @submit.prevent="tryToSend" class="space-y-3">
                        <div>
                            <label class="text-xs text-[#6B5C55] dark:text-[#C9B8A6]">Recipient (blank = broadcast)</label>
                            <input
                                v-model="form.recipient_uuid"
                                type="text"
                                placeholder="user uuid (or leave blank)"
                                class="w-full mt-1 rounded-lg bg-[#FBF5F0] dark:bg-[#2E2E2B] border border-[#EDE5DD] dark:border-[#3D3D39] px-3 py-2 text-xs font-mono text-[#3A2520] dark:text-[#F3EDE6] focus:outline-none focus:ring-2 focus:ring-[#C27B5B]"
                            />
                            <p class="text-[10px] text-[#6B5C55] dark:text-[#C9B8A6] mt-1">Find UUIDs in the Users page</p>
                        </div>
                        <div>
                            <label class="text-xs text-[#6B5C55] dark:text-[#C9B8A6]">Title</label>
                            <input
                                v-model="form.title"
                                type="text"
                                maxlength="120"
                                class="w-full mt-1 rounded-lg bg-[#FBF5F0] dark:bg-[#2E2E2B] border border-[#EDE5DD] dark:border-[#3D3D39] px-3 py-2 text-sm text-[#3A2520] dark:text-[#F3EDE6] focus:outline-none focus:ring-2 focus:ring-[#C27B5B]"
                            />
                        </div>
                        <div>
                            <label class="text-xs text-[#6B5C55] dark:text-[#C9B8A6]">Body</label>
                            <textarea
                                v-model="form.body"
                                rows="5"
                                maxlength="1000"
                                class="w-full mt-1 rounded-lg bg-[#FBF5F0] dark:bg-[#2E2E2B] border border-[#EDE5DD] dark:border-[#3D3D39] px-3 py-2 text-sm text-[#3A2520] dark:text-[#F3EDE6] focus:outline-none focus:ring-2 focus:ring-[#C27B5B]"
                            />
                        </div>
                        <label class="flex items-center gap-2 text-xs text-[#6B5C55] dark:text-[#C9B8A6]">
                            <input v-model="form.send_push" type="checkbox" />
                            Also send push notification
                        </label>
                        <button
                            type="submit"
                            :disabled="form.processing || !form.title || !form.body"
                            class="w-full py-2 rounded-lg bg-[#C27B5B] text-white text-sm font-semibold hover:bg-[#A8664A] disabled:opacity-50 transition"
                        >
                            {{ form.recipient_uuid ? 'Send Direct Message' : 'Send to All Users' }}
                        </button>
                    </form>
                </div>
            </aside>

            <!-- Sent messages history -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl overflow-hidden">
                    <div class="px-5 py-4 border-b border-[#EDE5DD] dark:border-[#3D3D39]">
                        <h2 class="font-serif text-base font-semibold text-[#3A2520] dark:text-[#F3EDE6]">Sent Messages</h2>
                    </div>
                    <div class="divide-y divide-[#EDE5DD] dark:divide-[#3D3D39]">
                        <article v-for="m in messages.data" :key="m.id" class="px-5 py-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h3 class="font-semibold text-sm text-[#3A2520] dark:text-[#F3EDE6]">{{ m.title }}</h3>
                                        <span v-if="m.recipient" class="px-2 py-0.5 rounded-full text-[10px] bg-[#C27B5B]/10 text-[#C27B5B]">
                                            direct
                                        </span>
                                        <span v-else class="px-2 py-0.5 rounded-full text-[10px] bg-[#7A8C5A]/15 text-[#7A8C5A]">
                                            broadcast → {{ m.recipients_count }}
                                        </span>
                                        <span v-if="m.sent_push" class="px-2 py-0.5 rounded-full text-[10px] bg-blue-100 text-blue-700">push</span>
                                    </div>
                                    <p class="text-xs text-[#6B5C55] dark:text-[#C9B8A6] mt-1.5">{{ m.body }}</p>
                                    <p class="text-[10px] text-[#6B5C55] dark:text-[#C9B8A6] mt-2">
                                        {{ formatDate(m.created_at) }}
                                        <template v-if="m.recipient"> &middot; to <Link :href="`/admin/users/${m.recipient.uuid}`" class="hover:text-[#C27B5B]">{{ m.recipient.name }}</Link></template>
                                    </p>
                                </div>
                            </div>
                        </article>
                        <div v-if="messages.data.length === 0" class="px-5 py-12 text-center text-[#6B5C55] dark:text-[#C9B8A6]">
                            No messages sent yet
                        </div>
                    </div>
                </div>

                <div v-if="messages.last_page > 1" class="mt-4 flex flex-wrap gap-1.5 justify-center">
                    <Link
                        v-for="link in messages.links"
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
            </div>
        </div>
    </AdminLayout>
</template>
