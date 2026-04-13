<script setup lang="ts">
import { useHead } from '@vueuse/head';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

function debounce<T extends (...args: any[]) => void>(fn: T, ms: number): T {
    let timer: ReturnType<typeof setTimeout> | null = null;
    return ((...args: any[]) => {
        if (timer) clearTimeout(timer);
        timer = setTimeout(() => fn(...args), ms);
    }) as T;
}

interface Transaction {
    id: number;
    type: string;
    amount: number;
    balance_after: number;
    description: string | null;
    created_at: string;
    user: {
        uuid: string;
        name: string;
        email: string;
    } | null;
}

interface Props {
    transactions: {
        data: Transaction[];
        current_page: number;
        last_page: number;
        total: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: {
        type: string | null;
        search: string | null;
    };
}

const props = defineProps<Props>();

useHead({ title: 'Token Transactions | Admin' });

const search = ref(props.filters.search ?? '');
const type = ref(props.filters.type ?? '');

const update = debounce(() => {
    router.get('/admin/tokens/transactions', {
        search: search.value || undefined,
        type: type.value || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true });
}, 300);

watch([search, type], update);

function formatDate(d: string): string {
    return new Date(d).toLocaleString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}
</script>

<template>
    <AdminLayout>
        <header class="mb-6">
            <h1 class="font-serif text-3xl font-bold text-[#3A2520] dark:text-[#F3EDE6]">Token Transactions</h1>
            <p class="text-sm text-[#6B5C55] dark:text-[#C9B8A6] mt-1">{{ transactions.total.toLocaleString() }} total</p>
        </header>

        <div class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-xl p-4 mb-4 flex flex-wrap gap-3">
            <input
                v-model="search"
                type="search"
                placeholder="Search by user name or email..."
                class="flex-1 min-w-[200px] rounded-lg bg-[#FBF5F0] dark:bg-[#2E2E2B] border border-[#EDE5DD] dark:border-[#3D3D39] px-3 py-2 text-sm text-[#3A2520] dark:text-[#F3EDE6] focus:outline-none focus:ring-2 focus:ring-[#C27B5B]"
            />
            <select v-model="type" class="rounded-lg bg-[#FBF5F0] dark:bg-[#2E2E2B] border border-[#EDE5DD] dark:border-[#3D3D39] px-3 py-2 text-sm text-[#3A2520] dark:text-[#F3EDE6]">
                <option value="">All types</option>
                <option value="purchase">Purchase</option>
                <option value="spend">Spend</option>
                <option value="bonus">Bonus</option>
                <option value="refund">Refund</option>
            </select>
        </div>

        <div class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-[#FBF5F0] dark:bg-[#2E2E2B] text-[#6B5C55] dark:text-[#C9B8A6] text-xs uppercase">
                    <tr>
                        <th class="text-left px-5 py-3">User</th>
                        <th class="text-left px-5 py-3">Type</th>
                        <th class="text-left px-5 py-3 hidden md:table-cell">Description</th>
                        <th class="text-right px-5 py-3">Amount</th>
                        <th class="text-right px-5 py-3 hidden lg:table-cell">When</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#EDE5DD] dark:divide-[#3D3D39]">
                    <tr v-for="t in transactions.data" :key="t.id" class="hover:bg-[#FBF5F0] dark:hover:bg-[#2E2E2B]">
                        <td class="px-5 py-3">
                            <Link v-if="t.user" :href="`/admin/users/${t.user.uuid}`" class="text-sm font-medium text-[#3A2520] dark:text-[#F3EDE6] hover:text-[#C27B5B]">
                                {{ t.user.name }}
                            </Link>
                            <span v-else class="text-[#6B5C55] dark:text-[#C9B8A6]">deleted</span>
                        </td>
                        <td class="px-5 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium uppercase"
                                :class="{
                                    'bg-emerald-100 text-emerald-700': t.type === 'purchase' || t.type === 'bonus',
                                    'bg-red-100 text-red-700': t.type === 'spend',
                                    'bg-amber-100 text-amber-700': t.type === 'refund',
                                }"
                            >
                                {{ t.type }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-xs text-[#6B5C55] dark:text-[#C9B8A6] hidden md:table-cell truncate max-w-[300px]">
                            {{ t.description }}
                        </td>
                        <td class="px-5 py-3 text-right font-mono text-sm" :class="t.amount > 0 ? 'text-emerald-600' : 'text-red-600'">
                            {{ t.amount > 0 ? '+' : '' }}{{ t.amount }}
                        </td>
                        <td class="px-5 py-3 text-right text-xs text-[#6B5C55] dark:text-[#C9B8A6] hidden lg:table-cell">{{ formatDate(t.created_at) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="transactions.last_page > 1" class="mt-4 flex flex-wrap gap-1.5 justify-center">
            <Link
                v-for="link in transactions.links"
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
