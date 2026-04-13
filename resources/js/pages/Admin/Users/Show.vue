<script setup lang="ts">
import { useHead } from '@vueuse/head';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ConfirmDialog from '@/shared/ConfirmDialog.vue';

interface Transaction {
    id: number;
    type: string;
    amount: number;
    balance_after: number;
    description: string | null;
    created_at: string;
}

interface Recipe {
    id: number;
    name: string;
    slug: string;
    created_at: string;
}

interface MealPlan {
    uuid: string;
    name: string;
    status: string;
    created_at: string;
}

interface Props {
    user: {
        uuid: string;
        name: string;
        username: string;
        email: string;
        token_balance: number;
        is_admin: boolean;
        email_verified_at: string | null;
        created_at: string;
        transactions: Transaction[];
        recipes: Recipe[];
        meal_plans: MealPlan[];
    };
}

const props = defineProps<Props>();

useHead({ title: `${props.user.name} | Admin` });

const adjustForm = useForm({
    amount: 0 as number,
    reason: '',
});

function adjustTokens() {
    if (!adjustForm.amount || !adjustForm.reason) return;
    adjustForm.post(`/admin/users/${props.user.uuid}/adjust-tokens`, {
        preserveScroll: true,
        onSuccess: () => adjustForm.reset(),
    });
}

type ActionKind = 'toggle-admin' | 'impersonate' | null;
const pendingAction = ref<ActionKind>(null);

const actionDialog = computed(() => {
    if (pendingAction.value === 'toggle-admin') {
        return props.user.is_admin
            ? {
                title: `Demote ${props.user.name}?`,
                message: 'They will lose access to the admin dashboard.',
                confirmLabel: 'Demote',
                variant: 'danger' as const,
            }
            : {
                title: `Promote ${props.user.name}?`,
                message: 'They will gain full access to the admin dashboard.',
                confirmLabel: 'Promote',
                variant: 'default' as const,
            };
    }
    if (pendingAction.value === 'impersonate') {
        return {
            title: `View as ${props.user.name}?`,
            message: "You'll be logged in as this user. Use the banner at the top of the page to stop impersonating.",
            confirmLabel: 'View as user',
            variant: 'default' as const,
        };
    }
    return { title: '', message: '', confirmLabel: 'Confirm', variant: 'default' as const };
});

function askToggleAdmin() {
    pendingAction.value = 'toggle-admin';
}

function askImpersonate() {
    pendingAction.value = 'impersonate';
}

function cancelAction() {
    pendingAction.value = null;
}

function confirmAction() {
    const action = pendingAction.value;
    pendingAction.value = null;

    if (action === 'toggle-admin') {
        router.post(`/admin/users/${props.user.uuid}/toggle-admin`, {}, { preserveScroll: true });
    } else if (action === 'impersonate') {
        router.post(`/admin/users/${props.user.uuid}/impersonate`);
    }
}

function verifyEmail() {
    router.post(`/admin/users/${props.user.uuid}/verify-email`, {}, { preserveScroll: true });
}

function formatDate(d: string): string {
    return new Date(d).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}
</script>

<template>
    <AdminLayout>
        <ConfirmDialog
            :open="pendingAction !== null"
            :title="actionDialog.title"
            :message="actionDialog.message"
            :confirm-label="actionDialog.confirmLabel"
            :variant="actionDialog.variant"
            @confirm="confirmAction"
            @cancel="cancelAction"
        />
        <Link href="/admin/users" class="inline-flex items-center gap-1.5 text-sm text-[#6B5C55] dark:text-[#C9B8A6] hover:text-[#C27B5B] mb-4">
            &larr; Back to users
        </Link>

        <header class="mb-6">
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="font-serif text-3xl font-bold text-[#3A2520] dark:text-[#F3EDE6]">{{ user.name }}</h1>
                <span v-if="user.is_admin" class="px-2 py-0.5 rounded-full text-xs font-medium bg-[#C27B5B]/10 text-[#C27B5B]">admin</span>
            </div>
            <p class="text-sm text-[#6B5C55] dark:text-[#C9B8A6] mt-1">@{{ user.username }} &middot; {{ user.email }}</p>
            <p class="text-xs text-[#6B5C55] dark:text-[#C9B8A6] mt-0.5">Joined {{ formatDate(user.created_at) }}</p>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Sidebar -->
            <aside class="lg:col-span-1 space-y-4">
                <div class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl p-5">
                    <p class="text-xs uppercase tracking-wider text-[#6B5C55] dark:text-[#C9B8A6] font-semibold">Token Balance</p>
                    <p class="font-serif text-4xl font-bold mt-1 text-[#3A2520] dark:text-[#F3EDE6]">{{ user.token_balance.toLocaleString() }}</p>
                </div>

                <div class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl p-5">
                    <h3 class="font-serif text-base font-semibold mb-3 text-[#3A2520] dark:text-[#F3EDE6]">Adjust Balance</h3>
                    <form @submit.prevent="adjustTokens" class="space-y-3">
                        <div>
                            <label class="text-xs text-[#6B5C55] dark:text-[#C9B8A6]">Amount (negative to remove)</label>
                            <input
                                v-model.number="adjustForm.amount"
                                type="number"
                                placeholder="e.g. 50 or -10"
                                class="w-full mt-1 rounded-lg bg-[#FBF5F0] dark:bg-[#2E2E2B] border border-[#EDE5DD] dark:border-[#3D3D39] px-3 py-2 text-sm text-[#3A2520] dark:text-[#F3EDE6] focus:outline-none focus:ring-2 focus:ring-[#C27B5B]"
                            />
                        </div>
                        <div>
                            <label class="text-xs text-[#6B5C55] dark:text-[#C9B8A6]">Reason</label>
                            <input
                                v-model="adjustForm.reason"
                                type="text"
                                placeholder="e.g. Customer support refund"
                                class="w-full mt-1 rounded-lg bg-[#FBF5F0] dark:bg-[#2E2E2B] border border-[#EDE5DD] dark:border-[#3D3D39] px-3 py-2 text-sm text-[#3A2520] dark:text-[#F3EDE6] focus:outline-none focus:ring-2 focus:ring-[#C27B5B]"
                            />
                        </div>
                        <button
                            type="submit"
                            :disabled="adjustForm.processing || !adjustForm.amount || !adjustForm.reason"
                            class="w-full py-2 rounded-lg bg-[#C27B5B] text-white text-sm font-semibold hover:bg-[#A8664A] disabled:opacity-50 transition"
                        >
                            Apply Adjustment
                        </button>
                    </form>
                </div>

                <!-- Admin Actions -->
                <div class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl p-5 space-y-2">
                    <h3 class="font-serif text-base font-semibold mb-3 text-[#3A2520] dark:text-[#F3EDE6]">Actions</h3>
                    <button
                        @click="askImpersonate"
                        class="w-full py-2 rounded-lg border border-[#EDE5DD] dark:border-[#3D3D39] text-sm font-medium text-[#3A2520] dark:text-[#F3EDE6] hover:border-[#C27B5B] hover:text-[#C27B5B] transition"
                    >
                        View as user
                    </button>
                    <button
                        @click="askToggleAdmin"
                        class="w-full py-2 rounded-lg border border-[#EDE5DD] dark:border-[#3D3D39] text-sm font-medium text-[#3A2520] dark:text-[#F3EDE6] hover:border-[#C27B5B] hover:text-[#C27B5B] transition"
                    >
                        {{ user.is_admin ? 'Demote from admin' : 'Promote to admin' }}
                    </button>
                    <button
                        v-if="!user.email_verified_at"
                        @click="verifyEmail"
                        class="w-full py-2 rounded-lg border border-[#EDE5DD] dark:border-[#3D3D39] text-sm font-medium text-[#3A2520] dark:text-[#F3EDE6] hover:border-[#C27B5B] hover:text-[#C27B5B] transition"
                    >
                        Mark email verified
                    </button>
                </div>
            </aside>

            <!-- Main content -->
            <div class="lg:col-span-2 space-y-4">
                <!-- Transactions -->
                <section class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl overflow-hidden">
                    <div class="px-5 py-4 border-b border-[#EDE5DD] dark:border-[#3D3D39]">
                        <h2 class="font-serif text-base font-semibold text-[#3A2520] dark:text-[#F3EDE6]">Recent Transactions</h2>
                    </div>
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-[#EDE5DD] dark:divide-[#3D3D39]">
                            <tr v-for="t in user.transactions" :key="t.id">
                                <td class="px-5 py-3">
                                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium uppercase"
                                        :class="{
                                            'bg-emerald-100 text-emerald-700': t.type === 'purchase' || t.type === 'bonus',
                                            'bg-red-100 text-red-700': t.type === 'spend',
                                            'bg-amber-100 text-amber-700': t.type === 'refund',
                                        }"
                                    >
                                        {{ t.type }}
                                    </span>
                                    <p class="text-xs text-[#6B5C55] dark:text-[#C9B8A6] mt-1">{{ t.description }}</p>
                                </td>
                                <td class="px-5 py-3 text-right font-mono text-sm" :class="t.amount > 0 ? 'text-emerald-600' : 'text-red-600'">
                                    {{ t.amount > 0 ? '+' : '' }}{{ t.amount }}
                                </td>
                                <td class="px-5 py-3 text-right text-xs text-[#6B5C55] dark:text-[#C9B8A6]">{{ formatDate(t.created_at) }}</td>
                            </tr>
                            <tr v-if="user.transactions.length === 0">
                                <td colspan="3" class="px-5 py-8 text-center text-[#6B5C55] dark:text-[#C9B8A6]">No transactions</td>
                            </tr>
                        </tbody>
                    </table>
                </section>

                <!-- Recipes -->
                <section class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl overflow-hidden">
                    <div class="px-5 py-4 border-b border-[#EDE5DD] dark:border-[#3D3D39]">
                        <h2 class="font-serif text-base font-semibold text-[#3A2520] dark:text-[#F3EDE6]">Recent Recipes ({{ user.recipes.length }})</h2>
                    </div>
                    <ul class="divide-y divide-[#EDE5DD] dark:divide-[#3D3D39]">
                        <li v-for="r in user.recipes" :key="r.id" class="px-5 py-3 flex items-center justify-between">
                            <a :href="`/recipe/${r.slug}`" target="_blank" class="text-sm font-medium text-[#3A2520] dark:text-[#F3EDE6] hover:text-[#C27B5B]">
                                {{ r.name }}
                            </a>
                            <span class="text-xs text-[#6B5C55] dark:text-[#C9B8A6]">{{ formatDate(r.created_at) }}</span>
                        </li>
                        <li v-if="user.recipes.length === 0" class="px-5 py-8 text-center text-[#6B5C55] dark:text-[#C9B8A6]">No recipes</li>
                    </ul>
                </section>
            </div>
        </div>
    </AdminLayout>
</template>
