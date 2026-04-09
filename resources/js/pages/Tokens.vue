<script setup lang="ts">
import { useHead } from '@vueuse/head';
import MyLayout from '@/layouts/MyLayout.vue';
import { ref } from 'vue';
import axios from 'axios';

interface TokenPackage {
    id: number;
    name: string;
    tokens: number;
    price_cents: number;
    savings?: string | null;
}

interface TokenTransaction {
    id: number;
    type: string;
    amount: number;
    description: string;
    created_at: string;
}

interface TokenStats {
    total_purchased: number;
    total_used: number;
}

const props = defineProps<{
    packages: TokenPackage[];
    balance: number;
    history: TokenTransaction[];
    stats: TokenStats;
}>();

useHead({
    title: 'Buy Tokens | FridgeGPT',
    meta: [
        { name: 'description', content: 'Purchase tokens to generate AI recipes with FridgeGPT.' },
    ],
});

const loadingPackageId = ref<number | null>(null);
const promoCode = ref('');
const promoLoading = ref(false);
const promoMessage = ref('');
const promoError = ref(false);

function formatPrice(cents: number): string {
    return '$' + (cents / 100).toFixed(2);
}

async function buyPackage(pkg: TokenPackage) {
    if (loadingPackageId.value) return;
    loadingPackageId.value = pkg.id;

    try {
        const { data } = await axios.post('/tokens/checkout', {
            package_id: pkg.id,
        });
        if (data.checkout_url) {
            window.location.href = data.checkout_url;
        }
    } catch {
        loadingPackageId.value = null;
    }
}

async function redeemPromo() {
    if (!promoCode.value.trim() || promoLoading.value) return;
    promoLoading.value = true;
    promoMessage.value = '';
    promoError.value = false;

    try {
        const { data } = await axios.post('/tokens/redeem', {
            code: promoCode.value.trim(),
        });
        promoMessage.value = data.message || 'Promo code redeemed!';
        promoError.value = false;
        promoCode.value = '';
    } catch (err: any) {
        promoMessage.value = err.response?.data?.message || 'Invalid promo code.';
        promoError.value = true;
    } finally {
        promoLoading.value = false;
    }
}
</script>

<template>
    <MyLayout>
        <main class="mx-auto max-w-4xl px-4 py-10 md:py-16 space-y-12" aria-label="Token purchase page">

            <!-- Balance Header -->
            <section class="text-center">
                <h1 class="text-3xl md:text-4xl font-bold font-serif text-[#3A2520] dark:text-[#EDE5DD] mb-2">Your Tokens</h1>
                <p class="text-lg text-[#6B5C55] dark:text-[#C9B8A6] mb-4">Each recipe generation uses 1 token.</p>
                <div class="inline-flex items-center gap-3 rounded-2xl bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] px-8 py-5 shadow-sm">
                    <span class="text-5xl font-bold text-[#C27B5B]" aria-live="polite">{{ balance }}</span>
                    <span class="text-lg text-[#6B5C55] dark:text-[#C9B8A6]">{{ balance === 1 ? 'token' : 'tokens' }} remaining</span>
                </div>
            </section>

            <!-- Package Cards -->
            <section aria-label="Token packages">
                <h2 class="text-2xl font-bold font-serif text-[#3A2520] dark:text-[#EDE5DD] mb-6 text-center">Buy Tokens</h2>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="pkg in packages"
                        :key="pkg.id"
                        class="relative flex flex-col rounded-2xl bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] p-6 shadow-sm hover:shadow-md hover:border-[#C27B5B] transition"
                        role="article"
                        :aria-label="pkg.name + ' package'"
                    >
                        <!-- Savings badge -->
                        <span
                            v-if="pkg.savings"
                            class="absolute -top-3 right-4 rounded-full bg-[#7A8C5A] px-3 py-1 text-xs font-bold text-white"
                        >
                            {{ pkg.savings }}
                        </span>

                        <h3 class="text-lg font-semibold text-[#3A2520] dark:text-[#EDE5DD] mb-1">{{ pkg.name }}</h3>
                        <p class="text-sm text-[#6B5C55] dark:text-[#C9B8A6] mb-4">{{ pkg.tokens }} recipes</p>
                        <p class="text-3xl font-bold text-[#3A2520] dark:text-[#EDE5DD] mb-6">{{ formatPrice(pkg.price_cents) }}</p>

                        <button
                            @click="buyPackage(pkg)"
                            :disabled="loadingPackageId !== null"
                            class="mt-auto w-full rounded-full bg-[#C27B5B] hover:bg-[#A8664A] px-5 py-3 text-white font-semibold transition focus:ring-2 focus:ring-[#C27B5B] focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed"
                            :aria-label="'Buy ' + pkg.name"
                        >
                            {{ loadingPackageId === pkg.id ? 'Redirecting...' : 'Buy Now' }}
                        </button>
                    </div>
                </div>
            </section>

            <!-- Transaction History -->
            <section v-if="history.length > 0" aria-label="Token transaction history">
                <h2 class="text-2xl font-bold font-serif text-[#3A2520] dark:text-[#EDE5DD] mb-4">Transaction History</h2>
                <div class="overflow-x-auto rounded-xl border border-[#EDE5DD] dark:border-[#3D3D39]">
                    <table class="w-full text-left text-sm" role="table">
                        <thead>
                            <tr class="bg-[#FBF5F0] dark:bg-[#2E2E2B] text-[#6B5C55] dark:text-[#C9B8A6]">
                                <th class="px-4 py-3 font-medium" scope="col">Date</th>
                                <th class="px-4 py-3 font-medium" scope="col">Description</th>
                                <th class="px-4 py-3 font-medium text-right" scope="col">Tokens</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#EDE5DD] dark:divide-[#3D3D39]">
                            <tr
                                v-for="tx in history"
                                :key="tx.id"
                                class="bg-white dark:bg-[#222220] hover:bg-[#FFF8F3] dark:hover:bg-[#2E2E2B] transition-colors"
                            >
                                <td class="px-4 py-3 text-[#3A2520] dark:text-[#EDE5DD] whitespace-nowrap">{{ tx.created_at }}</td>
                                <td class="px-4 py-3 text-[#3A2520] dark:text-[#EDE5DD]">{{ tx.description }}</td>
                                <td class="px-4 py-3 text-right font-medium whitespace-nowrap"
                                    :class="tx.amount > 0 ? 'text-[#7A8C5A]' : 'text-[#C27B5B]'"
                                >
                                    {{ tx.amount > 0 ? '+' : '' }}{{ tx.amount }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Promo Code -->
            <section aria-label="Redeem promo code" class="max-w-md mx-auto">
                <h2 class="text-xl font-bold font-serif text-[#3A2520] dark:text-[#EDE5DD] mb-3 text-center">Have a promo code?</h2>
                <form @submit.prevent="redeemPromo" class="flex gap-3">
                    <label for="promo-code" class="sr-only">Promo code</label>
                    <input
                        id="promo-code"
                        v-model="promoCode"
                        type="text"
                        placeholder="Enter promo code"
                        class="flex-1 rounded-full px-4 py-3 border border-[#EDE5DD] dark:border-[#3D3D39] bg-white dark:bg-[#262624] text-[#3A2520] dark:text-[#F3EDE6] placeholder-[#6B5C55] focus:outline-none focus:ring-2 focus:ring-[#C27B5B] text-base"
                        aria-label="Enter promo code"
                    />
                    <button
                        type="submit"
                        :disabled="!promoCode.trim() || promoLoading"
                        class="rounded-full bg-[#C27B5B] hover:bg-[#A8664A] px-6 py-3 text-white font-semibold transition focus:ring-2 focus:ring-[#C27B5B] focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {{ promoLoading ? 'Redeeming...' : 'Redeem' }}
                    </button>
                </form>
                <p
                    v-if="promoMessage"
                    class="mt-3 text-center text-sm font-medium"
                    :class="promoError ? 'text-red-600 dark:text-red-400' : 'text-[#7A8C5A]'"
                    role="alert"
                >
                    {{ promoMessage }}
                </p>
            </section>

        </main>
    </MyLayout>
</template>
