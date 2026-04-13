<script setup lang="ts">
import { useHead } from '@vueuse/head';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import ConfirmDialog from '@/shared/ConfirmDialog.vue';

interface Code {
    id: number;
    code: string;
    type: string;
    tokens: number;
    max_uses: number | null;
    uses_count: number;
    expires_at: string | null;
    is_active: boolean;
    created_at: string;
}

interface Props {
    codes: {
        data: Code[];
        current_page: number;
        last_page: number;
        total: number;
    };
}

const props = defineProps<Props>();

useHead({ title: 'Promo Codes | Admin' });

const form = useForm({
    code: '',
    tokens: 50 as number,
    max_uses: null as number | null,
    expires_at: '',
    type: 'promo',
});

function createCode() {
    form.post('/admin/tokens/codes', {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

function toggleActive(code: Code) {
    router.patch(`/admin/tokens/codes/${code.id}`, {
        is_active: !code.is_active,
    }, { preserveScroll: true });
}

const codeToDelete = ref<Code | null>(null);

function askDelete(code: Code) {
    codeToDelete.value = code;
}

function confirmDelete() {
    if (! codeToDelete.value) return;
    router.delete(`/admin/tokens/codes/${codeToDelete.value.id}`, {
        preserveScroll: true,
        onFinish: () => (codeToDelete.value = null),
    });
}

function formatDate(d: string | null): string {
    if (!d) return '—';
    return new Date(d).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}
</script>

<template>
    <AdminLayout>
        <ConfirmDialog
            :open="codeToDelete !== null"
            title="Delete promo code?"
            :message="codeToDelete ? `Code ${codeToDelete.code} will be permanently deleted. Users will no longer be able to redeem it.` : ''"
            confirm-label="Delete"
            variant="danger"
            @confirm="confirmDelete"
            @cancel="codeToDelete = null"
        />
        <header class="mb-6">
            <h1 class="font-serif text-3xl font-bold text-[#3A2520] dark:text-[#F3EDE6]">Promo Codes</h1>
            <p class="text-sm text-[#6B5C55] dark:text-[#C9B8A6] mt-1">{{ codes.total.toLocaleString() }} total</p>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Create form -->
            <aside class="lg:col-span-1">
                <div class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl p-5 sticky top-6">
                    <h2 class="font-serif text-base font-semibold mb-4 text-[#3A2520] dark:text-[#F3EDE6]">Create New Code</h2>
                    <form @submit.prevent="createCode" class="space-y-3">
                        <div>
                            <label class="text-xs text-[#6B5C55] dark:text-[#C9B8A6]">Code (optional)</label>
                            <input
                                v-model="form.code"
                                type="text"
                                placeholder="Auto-generated if blank"
                                class="w-full mt-1 rounded-lg bg-[#FBF5F0] dark:bg-[#2E2E2B] border border-[#EDE5DD] dark:border-[#3D3D39] px-3 py-2 text-sm uppercase tracking-wider text-[#3A2520] dark:text-[#F3EDE6] focus:outline-none focus:ring-2 focus:ring-[#C27B5B]"
                            />
                        </div>
                        <div>
                            <label class="text-xs text-[#6B5C55] dark:text-[#C9B8A6]">Tokens to grant</label>
                            <input
                                v-model.number="form.tokens"
                                type="number"
                                min="1"
                                class="w-full mt-1 rounded-lg bg-[#FBF5F0] dark:bg-[#2E2E2B] border border-[#EDE5DD] dark:border-[#3D3D39] px-3 py-2 text-sm text-[#3A2520] dark:text-[#F3EDE6] focus:outline-none focus:ring-2 focus:ring-[#C27B5B]"
                            />
                        </div>
                        <div>
                            <label class="text-xs text-[#6B5C55] dark:text-[#C9B8A6]">Type</label>
                            <select
                                v-model="form.type"
                                class="w-full mt-1 rounded-lg bg-[#FBF5F0] dark:bg-[#2E2E2B] border border-[#EDE5DD] dark:border-[#3D3D39] px-3 py-2 text-sm text-[#3A2520] dark:text-[#F3EDE6]"
                            >
                                <option value="promo">Promo</option>
                                <option value="bonus">Bonus</option>
                                <option value="referral">Referral</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs text-[#6B5C55] dark:text-[#C9B8A6]">Max uses (blank = unlimited)</label>
                            <input
                                v-model.number="form.max_uses"
                                type="number"
                                min="1"
                                class="w-full mt-1 rounded-lg bg-[#FBF5F0] dark:bg-[#2E2E2B] border border-[#EDE5DD] dark:border-[#3D3D39] px-3 py-2 text-sm text-[#3A2520] dark:text-[#F3EDE6] focus:outline-none focus:ring-2 focus:ring-[#C27B5B]"
                            />
                        </div>
                        <div>
                            <label class="text-xs text-[#6B5C55] dark:text-[#C9B8A6]">Expires at (optional)</label>
                            <input
                                v-model="form.expires_at"
                                type="date"
                                class="w-full mt-1 rounded-lg bg-[#FBF5F0] dark:bg-[#2E2E2B] border border-[#EDE5DD] dark:border-[#3D3D39] px-3 py-2 text-sm text-[#3A2520] dark:text-[#F3EDE6] focus:outline-none focus:ring-2 focus:ring-[#C27B5B]"
                            />
                        </div>
                        <button
                            type="submit"
                            :disabled="form.processing || !form.tokens"
                            class="w-full py-2 rounded-lg bg-[#C27B5B] text-white text-sm font-semibold hover:bg-[#A8664A] disabled:opacity-50 transition"
                        >
                            Create Code
                        </button>
                    </form>
                </div>
            </aside>

            <!-- Codes list -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-[#FBF5F0] dark:bg-[#2E2E2B] text-[#6B5C55] dark:text-[#C9B8A6] text-xs uppercase">
                            <tr>
                                <th class="text-left px-5 py-3">Code</th>
                                <th class="text-right px-5 py-3">Tokens</th>
                                <th class="text-right px-5 py-3 hidden md:table-cell">Uses</th>
                                <th class="text-right px-5 py-3 hidden md:table-cell">Expires</th>
                                <th class="text-right px-5 py-3">Status</th>
                                <th class="px-5 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#EDE5DD] dark:divide-[#3D3D39]">
                            <tr v-for="c in codes.data" :key="c.id" class="hover:bg-[#FBF5F0] dark:hover:bg-[#2E2E2B]">
                                <td class="px-5 py-3">
                                    <code class="text-sm font-mono font-bold text-[#C27B5B]">{{ c.code }}</code>
                                    <p class="text-xs text-[#6B5C55] dark:text-[#C9B8A6] uppercase">{{ c.type }}</p>
                                </td>
                                <td class="px-5 py-3 text-right font-medium text-[#3A2520] dark:text-[#F3EDE6]">{{ c.tokens }}</td>
                                <td class="px-5 py-3 text-right text-[#6B5C55] dark:text-[#C9B8A6] hidden md:table-cell">
                                    {{ c.uses_count }}{{ c.max_uses ? ` / ${c.max_uses}` : '' }}
                                </td>
                                <td class="px-5 py-3 text-right text-[#6B5C55] dark:text-[#C9B8A6] hidden md:table-cell">{{ formatDate(c.expires_at) }}</td>
                                <td class="px-5 py-3 text-right">
                                    <button
                                        @click="toggleActive(c)"
                                        class="px-2 py-0.5 rounded-full text-xs font-medium"
                                        :class="c.is_active
                                            ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200'
                                            : 'bg-[#EDE5DD] text-[#6B5C55] hover:bg-[#D4C7BB]'"
                                    >
                                        {{ c.is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <button @click="askDelete(c)" class="text-xs text-red-600 hover:underline">delete</button>
                                </td>
                            </tr>
                            <tr v-if="codes.data.length === 0">
                                <td colspan="6" class="px-5 py-12 text-center text-[#6B5C55] dark:text-[#C9B8A6]">No codes yet</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
