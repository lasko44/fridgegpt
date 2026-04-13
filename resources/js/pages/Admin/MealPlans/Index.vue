<script setup lang="ts">
import { useHead } from '@vueuse/head';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import ConfirmDialog from '@/shared/ConfirmDialog.vue';

function debounce<T extends (...args: any[]) => void>(fn: T, ms: number): T {
    let t: ReturnType<typeof setTimeout> | null = null;
    return ((...args: any[]) => {
        if (t) clearTimeout(t);
        t = setTimeout(() => fn(...args), ms);
    }) as T;
}

interface MealPlan {
    id: number;
    uuid: string;
    name: string;
    days: number;
    status: string;
    slots_count: number;
    tokens_spent: number;
    created_at: string;
    user: { uuid: string; name: string } | null;
}

interface Props {
    plans: {
        data: MealPlan[];
        current_page: number;
        last_page: number;
        total: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: { search: string | null; status: string | null };
}

const props = defineProps<Props>();
useHead({ title: 'Meal Plans | Admin' });

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');

const update = debounce(() => {
    router.get('/admin/meal-plans', {
        search: search.value || undefined,
        status: status.value || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true });
}, 300);

watch([search, status], update);

const planToDelete = ref<MealPlan | null>(null);

function askDelete(p: MealPlan) {
    planToDelete.value = p;
}

function confirmDelete() {
    if (! planToDelete.value) return;
    router.delete(`/admin/meal-plans/${planToDelete.value.uuid}`, {
        preserveScroll: true,
        onFinish: () => (planToDelete.value = null),
    });
}

function formatDate(d: string): string {
    return new Date(d).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}

const statusColors: Record<string, string> = {
    draft: 'bg-amber-100 text-amber-700',
    generating: 'bg-amber-100 text-amber-700',
    complete: 'bg-emerald-100 text-emerald-700',
    failed: 'bg-red-100 text-red-700',
};
</script>

<template>
    <AdminLayout>
        <ConfirmDialog
            :open="planToDelete !== null"
            title="Delete meal plan?"
            :message="planToDelete ? `${planToDelete.name} will be permanently removed along with all its recipes and grocery list.` : ''"
            confirm-label="Delete"
            variant="danger"
            @confirm="confirmDelete"
            @cancel="planToDelete = null"
        />
        <header class="mb-6">
            <h1 class="font-serif text-3xl font-bold text-[#3A2520] dark:text-[#F3EDE6]">Meal Plans</h1>
            <p class="text-sm text-[#6B5C55] dark:text-[#C9B8A6] mt-1">{{ plans.total.toLocaleString() }} total</p>
        </header>

        <div class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-xl p-4 mb-4 flex flex-wrap gap-3">
            <input
                v-model="search"
                type="search"
                placeholder="Search by name..."
                class="flex-1 min-w-[200px] rounded-lg bg-[#FBF5F0] dark:bg-[#2E2E2B] border border-[#EDE5DD] dark:border-[#3D3D39] px-3 py-2 text-sm text-[#3A2520] dark:text-[#F3EDE6] focus:outline-none focus:ring-2 focus:ring-[#C27B5B]"
            />
            <select v-model="status" class="rounded-lg bg-[#FBF5F0] dark:bg-[#2E2E2B] border border-[#EDE5DD] dark:border-[#3D3D39] px-3 py-2 text-sm text-[#3A2520] dark:text-[#F3EDE6]">
                <option value="">All statuses</option>
                <option value="draft">Draft</option>
                <option value="generating">Generating</option>
                <option value="complete">Complete</option>
                <option value="failed">Failed</option>
            </select>
        </div>

        <div class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-[#FBF5F0] dark:bg-[#2E2E2B] text-[#6B5C55] dark:text-[#C9B8A6] text-xs uppercase">
                    <tr>
                        <th class="text-left px-5 py-3">Plan</th>
                        <th class="text-left px-5 py-3 hidden md:table-cell">User</th>
                        <th class="text-right px-5 py-3">Days</th>
                        <th class="text-right px-5 py-3 hidden lg:table-cell">Slots</th>
                        <th class="text-right px-5 py-3 hidden lg:table-cell">Tokens</th>
                        <th class="text-right px-5 py-3">Status</th>
                        <th class="text-right px-5 py-3 hidden md:table-cell">Created</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#EDE5DD] dark:divide-[#3D3D39]">
                    <tr v-for="p in plans.data" :key="p.id" class="hover:bg-[#FBF5F0] dark:hover:bg-[#2E2E2B]">
                        <td class="px-5 py-3 font-medium text-[#3A2520] dark:text-[#F3EDE6]">{{ p.name }}</td>
                        <td class="px-5 py-3 hidden md:table-cell">
                            <Link v-if="p.user" :href="`/admin/users/${p.user.uuid}`" class="text-xs text-[#6B5C55] dark:text-[#C9B8A6] hover:text-[#C27B5B]">
                                {{ p.user.name }}
                            </Link>
                        </td>
                        <td class="px-5 py-3 text-right text-[#3A2520] dark:text-[#F3EDE6]">{{ p.days }}</td>
                        <td class="px-5 py-3 text-right text-[#6B5C55] dark:text-[#C9B8A6] hidden lg:table-cell">{{ p.slots_count }}</td>
                        <td class="px-5 py-3 text-right text-[#6B5C55] dark:text-[#C9B8A6] hidden lg:table-cell">{{ p.tokens_spent }}</td>
                        <td class="px-5 py-3 text-right">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium" :class="statusColors[p.status] || 'bg-[#EDE5DD] text-[#6B5C55]'">
                                {{ p.status }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right text-xs text-[#6B5C55] dark:text-[#C9B8A6] hidden md:table-cell">{{ formatDate(p.created_at) }}</td>
                        <td class="px-5 py-3 text-right">
                            <button @click="askDelete(p)" class="text-xs text-red-600 hover:underline">delete</button>
                        </td>
                    </tr>
                    <tr v-if="plans.data.length === 0">
                        <td colspan="8" class="px-5 py-12 text-center text-[#6B5C55] dark:text-[#C9B8A6]">No meal plans found</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="plans.last_page > 1" class="mt-4 flex flex-wrap gap-1.5 justify-center">
            <Link
                v-for="link in plans.links"
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
