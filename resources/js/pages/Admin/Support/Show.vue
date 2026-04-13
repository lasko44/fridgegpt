<script setup lang="ts">
import { useHead } from '@vueuse/head';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Link, router, useForm } from '@inertiajs/vue3';

interface Message {
    id: number;
    body: string;
    is_admin: boolean;
    created_at: string;
    sender: { name: string } | null;
}

interface Conversation {
    id: number;
    uuid: string;
    reference: string;
    subject: string;
    status: string;
    last_message_at: string;
    user: {
        uuid: string;
        name: string;
        username: string;
        email: string;
        token_balance: number;
        created_at: string;
    } | null;
    messages: Message[];
}

interface Props {
    conversation: Conversation;
}

const props = defineProps<Props>();

useHead({ title: `${props.conversation.reference} | Admin Support` });

const replyForm = useForm({
    body: '',
});

function sendReply() {
    if (! replyForm.body.trim()) return;
    replyForm.post(`/admin/support/${props.conversation.reference}/reply`, {
        preserveScroll: true,
        onSuccess: () => replyForm.reset(),
    });
}

function setStatus(status: string) {
    router.post(`/admin/support/${props.conversation.reference}/status`, { status }, {
        preserveScroll: true,
    });
}

const statusLabels: Record<string, string> = {
    awaiting_admin: 'Needs reply',
    awaiting_user: 'Awaiting user',
    resolved: 'Resolved',
    closed: 'Closed',
};

function formatDate(d: string): string {
    return new Date(d).toLocaleString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}
</script>

<template>
    <AdminLayout>
        <Link href="/admin/support" class="inline-flex items-center gap-1.5 text-sm text-[#6B5C55] dark:text-[#C9B8A6] hover:text-[#C27B5B] mb-4">
            &larr; Inbox
        </Link>

        <header class="mb-6">
            <div class="flex items-center gap-3 flex-wrap mb-2">
                <code class="text-sm font-mono font-bold text-[#C27B5B]">{{ conversation.reference }}</code>
                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-[#FBF5F0] dark:bg-[#2E2E2B] text-[#6B5C55] dark:text-[#C9B8A6]">
                    {{ statusLabels[conversation.status] }}
                </span>
            </div>
            <h1 class="font-serif text-2xl md:text-3xl font-bold text-[#3A2520] dark:text-[#F3EDE6]">{{ conversation.subject }}</h1>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Sidebar -->
            <aside class="lg:col-span-1 space-y-4">
                <!-- User card -->
                <div v-if="conversation.user" class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl p-5">
                    <p class="text-xs uppercase tracking-wider text-[#6B5C55] dark:text-[#C9B8A6] font-semibold mb-3">Customer</p>
                    <Link :href="`/admin/users/${conversation.user.uuid}`" class="block">
                        <p class="font-semibold text-sm text-[#3A2520] dark:text-[#F3EDE6] hover:text-[#C27B5B]">{{ conversation.user.name }}</p>
                        <p class="text-xs text-[#6B5C55] dark:text-[#C9B8A6] mt-0.5">@{{ conversation.user.username }}</p>
                        <p class="text-xs text-[#6B5C55] dark:text-[#C9B8A6]">{{ conversation.user.email }}</p>
                    </Link>
                    <div class="mt-3 pt-3 border-t border-[#EDE5DD] dark:border-[#3D3D39] flex justify-between text-xs">
                        <span class="text-[#6B5C55] dark:text-[#C9B8A6]">Tokens</span>
                        <span class="font-semibold text-[#3A2520] dark:text-[#F3EDE6]">{{ conversation.user.token_balance }}</span>
                    </div>
                </div>

                <!-- Status changer -->
                <div class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl p-5">
                    <p class="text-xs uppercase tracking-wider text-[#6B5C55] dark:text-[#C9B8A6] font-semibold mb-3">Status</p>
                    <div class="space-y-2">
                        <button
                            v-for="(label, value) in statusLabels"
                            :key="value"
                            @click="setStatus(value)"
                            class="w-full text-left px-3 py-2 rounded-lg text-sm font-medium transition"
                            :class="conversation.status === value
                                ? 'bg-[#C27B5B] text-white'
                                : 'bg-[#FBF5F0] dark:bg-[#2E2E2B] text-[#6B5C55] dark:text-[#C9B8A6] hover:bg-[#EDE5DD] dark:hover:bg-[#3D3D39]'"
                        >
                            {{ label }}
                        </button>
                    </div>
                </div>
            </aside>

            <!-- Main thread -->
            <div class="lg:col-span-2">
                <div class="space-y-4 mb-6">
                    <div
                        v-for="msg in conversation.messages"
                        :key="msg.id"
                        class="flex"
                        :class="msg.is_admin ? 'justify-end' : 'justify-start'"
                    >
                        <div
                            class="max-w-[80%] rounded-2xl p-4 shadow-sm"
                            :class="msg.is_admin
                                ? 'bg-[#C27B5B] text-white'
                                : 'bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39]'"
                        >
                            <p class="text-xs font-semibold mb-1" :class="msg.is_admin ? 'text-white/80' : 'text-[#C27B5B]'">
                                {{ msg.is_admin ? `${msg.sender?.name ?? 'You'} (Admin)` : (msg.sender?.name ?? 'Customer') }}
                            </p>
                            <p class="text-sm whitespace-pre-wrap" :class="msg.is_admin ? 'text-white' : 'text-[#3A2520] dark:text-[#F3EDE6]'">
                                {{ msg.body }}
                            </p>
                            <p class="text-[10px] mt-2" :class="msg.is_admin ? 'text-white/70' : 'text-[#6B5C55] dark:text-[#C9B8A6]'">
                                {{ formatDate(msg.created_at) }}
                            </p>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="sendReply" class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl p-4">
                    <textarea
                        v-model="replyForm.body"
                        rows="5"
                        maxlength="5000"
                        placeholder="Write a reply to the customer..."
                        class="w-full rounded-xl bg-[#FBF5F0] dark:bg-[#2E2E2B] border border-[#EDE5DD] dark:border-[#3D3D39] px-4 py-3 text-sm text-[#3A2520] dark:text-[#F3EDE6] focus:outline-none focus:ring-2 focus:ring-[#C27B5B]"
                    />
                    <div class="flex justify-end mt-3">
                        <button
                            type="submit"
                            :disabled="replyForm.processing || !replyForm.body.trim()"
                            class="px-5 py-2 rounded-full bg-[#C27B5B] text-white text-sm font-semibold hover:bg-[#A8664A] disabled:opacity-50 transition"
                        >
                            {{ replyForm.processing ? 'Sending...' : 'Send Reply' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
