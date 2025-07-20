<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { router } from '@inertiajs/vue3';
import MyLayout from '../layouts/MyLayout.vue';
import { loadStripe, Stripe, StripeCardElement, StripePaymentRequestButtonElement } from '@stripe/stripe-js';

const stripeToken = ref('');
const error = ref('');
const loading = ref(false);
const cardElement = ref<StripeCardElement | null>(null);
const stripe = ref<Stripe | null>(null);
const cardMount = ref<HTMLDivElement | null>(null);

const prButton = ref<StripePaymentRequestButtonElement | null>(null);
const prButtonMount = ref<HTMLDivElement | null>(null);
const paymentRequest = ref<any>(null);

onMounted(async () => {
    stripe.value = await loadStripe('pk_test_9aKCqOqHF5WbutLIVV0MZNqG00KMDpU2dh'); // Replace with your Stripe public key
    const elements = stripe.value!.elements();

    // Card Element
    cardElement.value = elements.create('card');
    cardElement.value.mount(cardMount.value!);

    // Payment Request Button (Apple Pay / Google Pay)
    paymentRequest.value = stripe.value!.paymentRequest({
        country: 'US',
        currency: 'usd',
        total: {
            label: 'Subscription',
            amount: 1000, // $10.00, adjust as needed
        },
        requestPayerName: true,
        requestPayerEmail: true,
    });

    paymentRequest.value.canMakePayment().then((result: any) => {
        if (result) {
            prButton.value = elements.create('paymentRequestButton', {
                paymentRequest: paymentRequest.value,
                style: {
                    paymentRequestButton: {
                        type: 'default',
                        theme: 'dark',
                        height: '44px',
                    },
                },
            });
            prButton.value.mount(prButtonMount.value!);
        }
    });

    paymentRequest.value.on('token', async ({ token, complete }: any) => {
        loading.value = true;
        error.value = '';
        try {
            await router.post('/subscriptions', {
                stripe_token: token.id,
            }, {
                onError: (errors) => {
                    error.value = errors.stripe_token || 'Subscription failed.';
                },
                onFinish: () => {
                    loading.value = false;
                }
            });
            complete('success');
        } catch {
            complete('fail');
            loading.value = false;
        }
    });
});

onBeforeUnmount(() => {
    if (cardElement.value) {
        cardElement.value.unmount();
    }
    if (prButton.value) {
        prButton.value.unmount();
    }
});

async function submitSubscription() {
    loading.value = true;
    error.value = '';
    if (!stripe.value || !cardElement.value) {
        error.value = 'Stripe not loaded.';
        loading.value = false;
        return;
    }
    const { token, error: stripeError } = await stripe.value.createToken(cardElement.value);
    if (stripeError) {
        error.value = stripeError.message || 'Card error.';
        loading.value = false;
        return;
    }
    stripeToken.value = token!.id;
    router.post('/subscriptions', {
        stripe_token: stripeToken.value,
    }, {
        onError: (errors) => {
            error.value = errors.stripe_token || 'Subscription failed.';
            loading.value = false;
        },
        onFinish: () => {
            loading.value = false;
        }
    });
}
</script>

<template>
    <MyLayout>
        <main>
            <section class="mx-auto mt-10 max-w-md rounded bg-white p-8 text-gray-900 shadow" aria-labelledby="subscription-heading">
                <h1 id="subscription-heading" class="mb-6 text-center text-3xl font-bold">Subscribe</h1>
                <form @submit.prevent="submitSubscription" class="flex flex-col gap-4" role="form">
                    <div>
                        <label class="mb-1 block font-medium text-gray-700">Card Details</label>
                        <div ref="cardMount" class="w-full rounded border px-4 py-2 bg-gray-50"></div>
                    </div>
                    <button
                        type="submit"
                        :disabled="loading"
                        class="rounded-xl hover:cursor-pointer bg-gradient-to-r from-teal-500 to-blue-500 px-4 py-2 text-lg font-bold text-white shadow transition hover:from-teal-600 hover:to-blue-600 focus:ring-2 focus:ring-blue-700 focus:outline-none"
                    >
                        {{ loading ? 'Processing...' : 'Start 1 Week Free Trial' }}
                    </button>
                    <p v-if="error" class="text-center text-red-600 mt-2">{{ error }}</p>
                </form>
                <div class="mt-6">
                    <div ref="prButtonMount"></div>
                </div>
                <p class="mt-4 text-center text-gray-600">You will not be charged until your free trial ends.</p>
            </section>
        </main>
    </MyLayout>
</template>