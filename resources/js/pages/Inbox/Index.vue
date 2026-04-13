<script setup lang="ts">
import { useHead } from '@vueuse/head';
import MyLayout from '@/layouts/MyLayout.vue';
import { Link } from '@inertiajs/vue3';

interface Notification {
    id: string;
    type: 'message' | 'support_reply';
    title: string;
    body: string;
    href: string | null;
    sender_name: string;
    is_broadcast: boolean;
    created_at: string;
}

interface Props {
    notifications: Notification[];
}

defineProps<Props>();

useHead({ title: 'Notifications | FridgeGPT' });

function formatDate(d: string): string {
    return new Date(d).toLocaleString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}
</script>

<template>
    <MyLayout>
        <div class="mx-auto max-w-3xl px-4 py-10">
            <header class="mb-6">
                <h1 class="font-serif text-3xl font-bold text-[#3A2520] dark:text-[#F3EDE6]">Notifications</h1>
                <p class="text-sm text-[#6B5C55] dark:text-[#C9B8A6] mt-1">Announcements, messages, and support updates</p>
            </header>

            <div v-if="notifications.length === 0" class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl p-12 text-center">
                <div class="mx-auto h-12 w-12 rounded-full bg-[#FBF5F0] dark:bg-[#2E2E2B] flex items-center justify-center mb-4">
                    <svg class="h-6 w-6 text-[#C27B5B]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </div>
                <h2 class="font-serif text-lg font-semibold text-[#3A2520] dark:text-[#F3EDE6]">All caught up</h2>
                <p class="text-sm text-[#6B5C55] dark:text-[#C9B8A6] mt-1">You have no notifications.</p>
            </div>

            <div v-else class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl overflow-hidden">
                <ul class="divide-y divide-[#EDE5DD] dark:divide-[#3D3D39]">
                    <li v-for="n in notifications" :key="n.id">
                        <component
                            :is="n.href ? Link : 'div'"
                            :href="n.href || undefined"
                            class="flex items-start gap-3 px-5 py-4 transition"
                            :class="n.href ? 'hover:bg-[#FBF5F0] dark:hover:bg-[#2E2E2B] cursor-pointer' : ''"
                        >
                            <!-- Icon -->
                            <div class="h-9 w-9 rounded-full flex items-center justify-center shrink-0 mt-0.5"
                                :class="n.type === 'support_reply' ? 'bg-blue-100 dark:bg-blue-900/30' : 'bg-[#C27B5B]/10'"
                            >
                                <!-- Support reply icon -->
                                <svg v-if="n.type === 'support_reply'" class="h-4 w-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                </svg>
                                <!-- Direct message icon -->
                                <svg v-else-if="!n.is_broadcast" class="h-4 w-4 text-[#C27B5B]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <!-- Broadcast icon -->
                                <svg v-else class="h-4 w-4 text-[#C27B5B]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                                </svg>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="font-semibold text-sm text-[#3A2520] dark:text-[#F3EDE6]">{{ n.title }}</h3>
                                    <span v-if="n.type === 'support_reply'" class="px-1.5 py-0.5 rounded-full text-[9px] bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 uppercase tracking-wider font-bold">
                                        support
                                    </span>
                                    <span v-else-if="n.is_broadcast" class="px-1.5 py-0.5 rounded-full text-[9px] bg-[#7A8C5A]/15 text-[#7A8C5A] uppercase tracking-wider font-bold">
                                        announcement
                                    </span>
                                </div>
                                <p class="text-sm text-[#6B5C55] dark:text-[#C9B8A6] mt-1 line-clamp-2 whitespace-pre-wrap">{{ n.body }}</p>
                                <div class="flex items-center gap-2 mt-2 text-[10px] text-[#6B5C55] dark:text-[#C9B8A6]">
                                    <span>{{ n.sender_name }}</span>
                                    <span>&middot;</span>
                                    <span>{{ formatDate(n.created_at) }}</span>
                                </div>
                                <p v-if="n.href" class="text-xs font-semibold text-[#C27B5B] mt-1.5">
                                    View conversation &rarr;
                                </p>
                            </div>
                        </component>
                    </li>
                </ul>
            </div>
        </div>
    </MyLayout>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
