<script setup lang="ts">
import { useHead } from '@vueuse/head';
import MyLayout from '@/layouts/MyLayout.vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import { onMounted, onUnmounted } from 'vue';

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
    messages: Message[];
}

interface Props {
    conversation: Conversation;
}

const props = defineProps<Props>();

useHead({ title: `${props.conversation.subject} | Support` });

const replyForm = useForm({
    body: '',
});

function sendReply() {
    if (! replyForm.body.trim()) return;
    replyForm.post(`/support/${props.conversation.reference}/reply`, {
        preserveScroll: true,
        onSuccess: () => replyForm.reset(),
    });
}

const statusLabels: Record<string, string> = {
    awaiting_admin: 'Waiting on us',
    awaiting_user: 'Waiting on you',
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

const isOpen = () => ['awaiting_admin', 'awaiting_user'].includes(props.conversation.status);

// Poll every 5s for new admin replies
let pollInterval: ReturnType<typeof setInterval> | null = null;
onMounted(() => {
    pollInterval = setInterval(() => {
        router.reload({ only: ['conversation'], preserveScroll: true });
    }, 5000);
});
onUnmounted(() => {
    if (pollInterval) clearInterval(pollInterval);
});
</script>

<template>
    <MyLayout>
        <div class="mx-auto max-w-3xl px-4 py-10">
            <Link href="/support" class="inline-flex items-center gap-1.5 text-sm text-[#6B5C55] dark:text-[#C9B8A6] hover:text-[#C27B5B] mb-4">
                &larr; All conversations
            </Link>

            <header class="mb-6">
                <div class="flex items-center gap-3 flex-wrap mb-2">
                    <code class="text-xs font-mono font-bold text-[#C27B5B]">{{ conversation.reference }}</code>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-medium" :class="statusClasses[conversation.status]">
                        {{ statusLabels[conversation.status] }}
                    </span>
                </div>
                <h1 class="font-serif text-2xl md:text-3xl font-bold text-[#3A2520] dark:text-[#F3EDE6]">{{ conversation.subject }}</h1>
            </header>

            <!-- Messages thread -->
            <div class="space-y-4 mb-6">
                <div
                    v-for="msg in conversation.messages"
                    :key="msg.id"
                    class="flex"
                    :class="msg.is_admin ? 'justify-start' : 'justify-end'"
                >
                    <div
                        class="max-w-[80%] rounded-2xl p-4 shadow-sm"
                        :class="msg.is_admin
                            ? 'bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39]'
                            : 'bg-[#C27B5B] text-white'"
                    >
                        <p class="text-xs font-semibold mb-1" :class="msg.is_admin ? 'text-[#C27B5B]' : 'text-white/80'">
                            {{ msg.is_admin ? `${msg.sender?.name ?? 'Support'} (FridgeGPT)` : 'You' }}
                        </p>
                        <p class="text-sm whitespace-pre-wrap" :class="msg.is_admin ? 'text-[#3A2520] dark:text-[#F3EDE6]' : 'text-white'">
                            {{ msg.body }}
                        </p>
                        <p class="text-[10px] mt-2" :class="msg.is_admin ? 'text-[#6B5C55] dark:text-[#C9B8A6]' : 'text-white/70'">
                            {{ formatDate(msg.created_at) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Reply form -->
            <form v-if="isOpen()" @submit.prevent="sendReply" class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl p-4">
                <textarea
                    v-model="replyForm.body"
                    rows="4"
                    placeholder="Write a reply..."
                    maxlength="5000"
                    class="w-full rounded-xl bg-[#FBF5F0] dark:bg-[#2E2E2B] border border-[#EDE5DD] dark:border-[#3D3D39] px-4 py-3 text-sm text-[#3A2520] dark:text-[#F3EDE6] focus:outline-none focus:ring-2 focus:ring-[#C27B5B]"
                />
                <div class="flex justify-end mt-3">
                    <button
                        type="submit"
                        :disabled="replyForm.processing || !replyForm.body.trim()"
                        class="px-5 py-2 rounded-full bg-[#C27B5B] text-white text-sm font-semibold hover:bg-[#A8664A] disabled:opacity-50 transition"
                    >
                        {{ replyForm.processing ? 'Sending...' : 'Reply' }}
                    </button>
                </div>
            </form>

            <div v-else class="bg-[#FBF5F0] dark:bg-[#2E2E2B] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl p-5 text-center">
                <p class="text-sm text-[#6B5C55] dark:text-[#C9B8A6]">
                    This conversation is {{ conversation.status }}. <Link href="/support/new" class="text-[#C27B5B] hover:underline">Start a new one</Link> if you need more help.
                </p>
            </div>
        </div>
    </MyLayout>
</template>
