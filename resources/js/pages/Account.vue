<script setup lang="ts">
import MyLayout from '@/layouts/MyLayout.vue';
import Profile from '@/shared/AccountComponents/Profile.vue';
import { ref, onMounted } from 'vue';
import { usePage, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { loadStripe } from '@stripe/stripe-js';

const page = usePage();
const user = (page.props as any).auth?.user;

const activeTab = ref('profile');
const promoCode = ref('');
const promoMessage = ref('');
const promoError = ref('');

// Payment method state
const pmLast4 = ref('');
const pmBrand = ref('');
const showCardForm = ref(false);
const savingCard = ref(false);
const cardError = ref('');
const cardSuccess = ref('');

let stripe: any = null;
let elements: any = null;
let cardElement: any = null;

onMounted(async () => {
    try {
        const { data } = await axios.get('/api/payment-method');
        if (data.last4) {
            pmLast4.value = data.last4;
            pmBrand.value = data.brand;
        }
    } catch {
        // No payment method saved
    }
});

async function initCardForm() {
    showCardForm.value = true;
    cardError.value = '';
    cardSuccess.value = '';

    // Load Stripe
    if (!stripe) {
        stripe = await loadStripe(import.meta.env.VITE_STRIPE_KEY || '');
    }

    // Wait for DOM to render
    await new Promise(r => setTimeout(r, 100));

    elements = stripe.elements();
    cardElement = elements.create('card', {
        style: {
            base: {
                fontSize: '16px',
                color: '#3A2520',
                '::placeholder': { color: '#B0A196' },
            },
        },
    });
    cardElement.mount('#card-element');
}

async function saveCard() {
    savingCard.value = true;
    cardError.value = '';

    try {
        // Get a SetupIntent from the server
        const { data } = await axios.post('/api/setup-intent');

        const { setupIntent, error } = await stripe.confirmCardSetup(data.client_secret, {
            payment_method: { card: cardElement },
        });

        if (error) {
            cardError.value = error.message;
            return;
        }

        // Save the payment method on the server
        await axios.post('/api/save-payment-method', {
            payment_method: setupIntent.payment_method,
        });

        // Update display
        const { data: pm } = await axios.get('/api/payment-method');
        pmLast4.value = pm.last4;
        pmBrand.value = pm.brand;

        showCardForm.value = false;
        cardSuccess.value = 'Payment method saved!';
        cardElement?.destroy();
    } catch (e: any) {
        cardError.value = e.response?.data?.message || 'Failed to save card.';
    } finally {
        savingCard.value = false;
    }
}

async function redeemCode() {
    promoMessage.value = '';
    promoError.value = '';
    try {
        router.post('/tokens/redeem', { code: promoCode.value }, {
            onSuccess: () => {
                promoMessage.value = 'Code redeemed!';
                promoCode.value = '';
            },
            onError: (errors) => {
                promoError.value = errors.code || 'Invalid code.';
            },
            preserveScroll: true,
        });
    } catch {
        promoError.value = 'Something went wrong.';
    }
}
</script>

<template>
    <MyLayout>
        <section class="mx-auto max-w-3xl px-4 py-10">
            <h1 class="text-3xl font-bold font-serif text-[#3A2520] dark:text-[#E8E0D4] mb-8">Account</h1>

            <!-- Tabs -->
            <div class="flex gap-1 mb-8 border-b border-[#EDE5DD] dark:border-[#3D3D39]" role="tablist" aria-label="Account sections">
                <button
                    @click="activeTab = 'profile'"
                    :class="activeTab === 'profile' ? 'border-[#C27B5B] text-[#C27B5B]' : 'border-transparent text-[#6B5C55] dark:text-[#C9B8A6] hover:text-[#3A2520] dark:hover:text-[#E8E0D4]'"
                    class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition"
                    role="tab" :aria-selected="activeTab === 'profile'"
                >
                    Profile
                </button>
                <button
                    @click="activeTab = 'payment'"
                    :class="activeTab === 'payment' ? 'border-[#C27B5B] text-[#C27B5B]' : 'border-transparent text-[#6B5C55] dark:text-[#C9B8A6] hover:text-[#3A2520] dark:hover:text-[#E8E0D4]'"
                    class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition"
                    role="tab" :aria-selected="activeTab === 'payment'"
                >
                    Payment & Tokens
                </button>
                <button
                    @click="activeTab = 'delete'"
                    :class="activeTab === 'delete' ? 'border-red-500 text-red-500' : 'border-transparent text-[#6B5C55] dark:text-[#C9B8A6] hover:text-red-500'"
                    class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition"
                    role="tab" :aria-selected="activeTab === 'delete'"
                >
                    Danger Zone
                </button>
            </div>

            <!-- Profile -->
            <div v-show="activeTab === 'profile'" role="tabpanel">
                <Profile />
            </div>

            <!-- Payment & Tokens -->
            <div v-show="activeTab === 'payment'" role="tabpanel" class="space-y-6">
                <!-- Token balance -->
                <div class="rounded-2xl bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] p-6">
                    <h2 class="text-lg font-semibold font-serif text-[#3A2520] dark:text-[#E8E0D4] mb-4">Token Balance</h2>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-3xl font-bold text-[#C27B5B] dark:text-[#D4967E]">{{ user?.token_balance ?? 0 }}</p>
                            <p class="text-sm text-[#6B5C55] dark:text-[#C9B8A6]">tokens remaining</p>
                        </div>
                        <Link
                            href="/tokens"
                            class="rounded-xl bg-[#C27B5B] hover:bg-[#A8664A] px-5 py-2.5 text-sm font-semibold text-white transition focus:ring-2 focus:ring-[#C27B5B] focus:outline-none"
                        >
                            Buy More
                        </Link>
                    </div>
                </div>

                <!-- Payment method -->
                <div class="rounded-2xl bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] p-6">
                    <h2 class="text-lg font-semibold font-serif text-[#3A2520] dark:text-[#E8E0D4] mb-4">Payment Method</h2>

                    <!-- Saved card display -->
                    <div v-if="pmLast4 && !showCardForm" class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-14 rounded-lg bg-[#FBF5F0] dark:bg-[#2E2E2B] flex items-center justify-center">
                                <span class="text-xs font-bold text-[#6B5C55] dark:text-[#C9B8A6] uppercase">{{ pmBrand }}</span>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-[#3A2520] dark:text-[#E8E0D4]">&bull;&bull;&bull;&bull; {{ pmLast4 }}</p>
                                <p class="text-xs text-[#6B5C55] dark:text-[#C9B8A6]">{{ pmBrand }}</p>
                            </div>
                        </div>
                        <button
                            @click="initCardForm"
                            class="text-sm text-[#C27B5B] hover:underline"
                        >
                            Change
                        </button>
                    </div>

                    <!-- No card yet -->
                    <div v-if="!pmLast4 && !showCardForm">
                        <p class="text-sm text-[#6B5C55] dark:text-[#C9B8A6] mb-3">No payment method saved.</p>
                        <button
                            @click="initCardForm"
                            class="rounded-xl border border-[#EDE5DD] dark:border-[#3D3D39] px-4 py-2.5 text-sm font-medium text-[#3A2520] dark:text-[#E8E0D4] hover:bg-[#FBF5F0] dark:hover:bg-[#2E2E2B] transition"
                        >
                            Add Payment Method
                        </button>
                    </div>

                    <!-- Inline card form -->
                    <div v-if="showCardForm" class="space-y-4">
                        <div id="card-element" class="rounded-xl border border-[#EDE5DD] dark:border-[#3D3D39] bg-[#FBF5F0] dark:bg-[#2E2E2B] p-4"></div>
                        <p v-if="cardError" class="text-sm text-red-500">{{ cardError }}</p>
                        <div class="flex gap-3">
                            <button
                                @click="saveCard"
                                :disabled="savingCard"
                                class="rounded-xl bg-[#C27B5B] hover:bg-[#A8664A] px-5 py-2.5 text-sm font-semibold text-white transition disabled:opacity-50"
                            >
                                {{ savingCard ? 'Saving...' : 'Save Card' }}
                            </button>
                            <button
                                @click="showCardForm = false; cardElement?.destroy()"
                                class="rounded-xl border border-[#EDE5DD] dark:border-[#3D3D39] px-5 py-2.5 text-sm font-medium text-[#6B5C55] dark:text-[#C9B8A6] hover:bg-[#FBF5F0] dark:hover:bg-[#2E2E2B] transition"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>

                    <p v-if="cardSuccess" class="mt-3 text-sm text-[#7A8C5A]">{{ cardSuccess }}</p>
                </div>

                <!-- Promo code -->
                <div class="rounded-2xl bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] p-6">
                    <h2 class="text-lg font-semibold font-serif text-[#3A2520] dark:text-[#E8E0D4] mb-2">Promo Code</h2>
                    <p class="text-sm text-[#6B5C55] dark:text-[#C9B8A6] mb-4">Have a promo code? Redeem it for free tokens.</p>
                    <form @submit.prevent="redeemCode" class="flex gap-2">
                        <input
                            v-model="promoCode"
                            type="text"
                            placeholder="Enter code"
                            class="flex-1 rounded-xl border border-[#EDE5DD] dark:border-[#3D3D39] bg-[#FBF5F0] dark:bg-[#2E2E2B] text-[#3A2520] dark:text-[#E8E0D4] placeholder-[#B0A196] px-4 py-2.5 text-sm focus:ring-2 focus:ring-[#C27B5B] focus:outline-none"
                            aria-label="Promo code"
                        />
                        <button
                            type="submit"
                            :disabled="!promoCode.trim()"
                            class="rounded-xl bg-[#C27B5B] hover:bg-[#A8664A] px-5 py-2.5 text-sm font-semibold text-white transition focus:ring-2 focus:ring-[#C27B5B] focus:outline-none disabled:opacity-40"
                        >
                            Redeem
                        </button>
                    </form>
                    <p v-if="promoMessage" class="mt-2 text-sm text-[#7A8C5A]">{{ promoMessage }}</p>
                    <p v-if="promoError" class="mt-2 text-sm text-red-500">{{ promoError }}</p>
                </div>
            </div>

            <!-- Delete Account -->
            <div v-show="activeTab === 'delete'" role="tabpanel">
                <div class="rounded-2xl bg-white dark:bg-[#222220] border border-red-200 dark:border-red-900/30 p-6">
                    <h2 class="text-lg font-semibold font-serif text-red-600 dark:text-red-400 mb-2">Delete Account</h2>
                    <p class="text-sm text-[#6B5C55] dark:text-[#C9B8A6] mb-4">
                        Once your account is deleted, all of your recipes and data will be permanently removed. This action cannot be undone.
                    </p>
                    <button
                        class="rounded-xl bg-red-600 hover:bg-red-700 px-5 py-2.5 text-sm font-semibold text-white transition focus:ring-2 focus:ring-red-500 focus:outline-none"
                    >
                        Delete My Account
                    </button>
                </div>
            </div>
        </section>
    </MyLayout>
</template>
