<script setup lang="ts">
import { useHead } from '@vueuse/head';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ConfirmDialog from '@/shared/ConfirmDialog.vue';

interface FailedJob {
    id: number;
    uuid: string;
    connection: string;
    queue: string;
    job_class: string;
    exception: string;
    failed_at: string;
}

interface PendingJob {
    id: number;
    queue: string;
    job_class: string;
    attempts: number;
    reserved_at: number | null;
    available_at: number;
    created_at: number;
}

interface Props {
    failedJobs: FailedJob[];
    pendingJobs: PendingJob[];
    stats: { pending: number; failed: number };
}

defineProps<Props>();
useHead({ title: 'System | Admin' });

type ActionKind = 'delete-one' | 'retry-all' | 'flush-all' | null;
const pendingAction = ref<ActionKind>(null);
const pendingJobUuid = ref<string | null>(null);

const dialogConfig = computed(() => {
    switch (pendingAction.value) {
        case 'delete-one':
            return {
                title: 'Delete failed job?',
                message: 'This job will be permanently removed and cannot be retried.',
                confirmLabel: 'Delete',
                variant: 'danger' as const,
            };
        case 'retry-all':
            return {
                title: 'Retry all failed jobs?',
                message: 'Every failed job will be re-queued for processing.',
                confirmLabel: 'Retry all',
                variant: 'default' as const,
            };
        case 'flush-all':
            return {
                title: 'Delete all failed jobs?',
                message: 'Every failed job will be permanently removed. This cannot be undone.',
                confirmLabel: 'Delete all',
                variant: 'danger' as const,
            };
        default:
            return { title: '', message: '', confirmLabel: 'Confirm', variant: 'default' as const };
    }
});

function retry(uuid: string) {
    router.post(`/admin/system/jobs/${uuid}/retry`, {}, { preserveScroll: true });
}

function askDelete(uuid: string) {
    pendingJobUuid.value = uuid;
    pendingAction.value = 'delete-one';
}

function askRetryAll() {
    pendingAction.value = 'retry-all';
}

function askFlushAll() {
    pendingAction.value = 'flush-all';
}

function cancelAction() {
    pendingAction.value = null;
    pendingJobUuid.value = null;
}

function confirmAction() {
    const action = pendingAction.value;
    pendingAction.value = null;

    if (action === 'delete-one' && pendingJobUuid.value) {
        const uuid = pendingJobUuid.value;
        pendingJobUuid.value = null;
        router.delete(`/admin/system/jobs/${uuid}`, { preserveScroll: true });
    } else if (action === 'retry-all') {
        router.post('/admin/system/retry-all', {}, { preserveScroll: true });
    } else if (action === 'flush-all') {
        router.post('/admin/system/flush-failed', {}, { preserveScroll: true });
    }
}

function fmtTime(t: string | number | null): string {
    if (!t) return '—';
    const d = typeof t === 'string' ? new Date(t) : new Date(t * 1000);
    return d.toLocaleString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}
</script>

<template>
    <AdminLayout>
        <ConfirmDialog
            :open="pendingAction !== null"
            :title="dialogConfig.title"
            :message="dialogConfig.message"
            :confirm-label="dialogConfig.confirmLabel"
            :variant="dialogConfig.variant"
            @confirm="confirmAction"
            @cancel="cancelAction"
        />
        <header class="mb-6">
            <h1 class="font-serif text-3xl font-bold text-[#3A2520] dark:text-[#F3EDE6]">System</h1>
            <p class="text-sm text-[#6B5C55] dark:text-[#C9B8A6] mt-1">Queue and background jobs</p>
        </header>

        <div class="grid grid-cols-2 gap-4 mb-6">
            <div class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl p-5">
                <p class="text-xs uppercase tracking-wider text-[#6B5C55] dark:text-[#C9B8A6] font-semibold">Pending Jobs</p>
                <p class="font-serif text-3xl font-bold mt-1 text-[#3A2520] dark:text-[#F3EDE6]">{{ stats.pending }}</p>
            </div>
            <div class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl p-5">
                <p class="text-xs uppercase tracking-wider text-[#6B5C55] dark:text-[#C9B8A6] font-semibold">Failed Jobs</p>
                <p class="font-serif text-3xl font-bold mt-1" :class="stats.failed > 0 ? 'text-red-600' : 'text-[#3A2520] dark:text-[#F3EDE6]'">
                    {{ stats.failed }}
                </p>
            </div>
        </div>

        <!-- Failed jobs -->
        <section class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl overflow-hidden mb-6">
            <div class="px-5 py-4 border-b border-[#EDE5DD] dark:border-[#3D3D39] flex items-center justify-between flex-wrap gap-2">
                <h2 class="font-serif text-base font-semibold text-[#3A2520] dark:text-[#F3EDE6]">Failed Jobs</h2>
                <div v-if="failedJobs.length > 0" class="flex gap-2">
                    <button @click="askRetryAll" class="text-xs px-3 py-1.5 rounded-lg bg-[#C27B5B] text-white hover:bg-[#A8664A] font-semibold">
                        Retry all
                    </button>
                    <button @click="askFlushAll" class="text-xs px-3 py-1.5 rounded-lg border border-red-300 text-red-600 hover:bg-red-50 font-semibold">
                        Delete all
                    </button>
                </div>
            </div>
            <div class="divide-y divide-[#EDE5DD] dark:divide-[#3D3D39] max-h-[600px] overflow-y-auto">
                <article v-for="job in failedJobs" :key="job.id" class="px-5 py-4">
                    <div class="flex items-start justify-between gap-3 mb-2">
                        <div class="flex-1 min-w-0">
                            <p class="font-mono text-xs font-bold text-[#3A2520] dark:text-[#F3EDE6]">{{ job.job_class }}</p>
                            <p class="text-xs text-[#6B5C55] dark:text-[#C9B8A6] mt-0.5">{{ job.queue }} &middot; {{ fmtTime(job.failed_at) }}</p>
                        </div>
                        <div class="flex gap-2 shrink-0">
                            <button @click="retry(job.uuid)" class="text-xs px-2 py-1 rounded border border-[#EDE5DD] dark:border-[#3D3D39] text-[#C27B5B] hover:bg-[#C27B5B]/10">retry</button>
                            <button @click="askDelete(job.uuid)" class="text-xs px-2 py-1 rounded border border-[#EDE5DD] dark:border-[#3D3D39] text-red-600 hover:bg-red-50">delete</button>
                        </div>
                    </div>
                    <details class="mt-2">
                        <summary class="text-xs text-[#6B5C55] dark:text-[#C9B8A6] cursor-pointer hover:text-[#C27B5B]">Show error</summary>
                        <pre class="mt-2 text-[10px] font-mono whitespace-pre-wrap break-all bg-[#FBF5F0] dark:bg-[#2E2E2B] p-3 rounded text-[#3A2520] dark:text-[#F3EDE6]">{{ job.exception }}</pre>
                    </details>
                </article>
                <div v-if="failedJobs.length === 0" class="px-5 py-12 text-center text-[#6B5C55] dark:text-[#C9B8A6]">
                    No failed jobs 🎉
                </div>
            </div>
        </section>

        <!-- Pending jobs -->
        <section class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl overflow-hidden">
            <div class="px-5 py-4 border-b border-[#EDE5DD] dark:border-[#3D3D39]">
                <h2 class="font-serif text-base font-semibold text-[#3A2520] dark:text-[#F3EDE6]">Pending Jobs</h2>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-[#FBF5F0] dark:bg-[#2E2E2B] text-[#6B5C55] dark:text-[#C9B8A6] text-xs uppercase">
                    <tr>
                        <th class="text-left px-5 py-3">Job</th>
                        <th class="text-left px-5 py-3 hidden md:table-cell">Queue</th>
                        <th class="text-right px-5 py-3">Attempts</th>
                        <th class="text-right px-5 py-3 hidden lg:table-cell">Reserved</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#EDE5DD] dark:divide-[#3D3D39]">
                    <tr v-for="job in pendingJobs" :key="job.id">
                        <td class="px-5 py-3 font-mono text-xs text-[#3A2520] dark:text-[#F3EDE6]">{{ job.job_class }}</td>
                        <td class="px-5 py-3 text-xs text-[#6B5C55] dark:text-[#C9B8A6] hidden md:table-cell">{{ job.queue }}</td>
                        <td class="px-5 py-3 text-right text-xs">{{ job.attempts }}</td>
                        <td class="px-5 py-3 text-right text-xs text-[#6B5C55] dark:text-[#C9B8A6] hidden lg:table-cell">{{ fmtTime(job.reserved_at) }}</td>
                    </tr>
                    <tr v-if="pendingJobs.length === 0">
                        <td colspan="4" class="px-5 py-8 text-center text-[#6B5C55] dark:text-[#C9B8A6]">No pending jobs</td>
                    </tr>
                </tbody>
            </table>
        </section>
    </AdminLayout>
</template>
