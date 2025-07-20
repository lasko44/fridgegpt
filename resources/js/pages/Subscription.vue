<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue';
import MyLayout from '../layouts/MyLayout.vue';
import { loadStripe, Stripe, StripePaymentElement, StripePaymentRequestButtonElement } from '@stripe/stripe-js';

const stripe = ref<Stripe | null>(null);
const elements = ref<any>(null);
const paymentElement = ref<StripePaymentElement | null>(null);
const paymentElementMount = ref<HTMLDivElement | null>(null);

const prButton = ref<StripePaymentRequestButtonElement | null>(null);
const prButtonMount = ref<HTMLDivElement | null>(null);
const paymentRequest = ref<any>(null);
const showPrButton = ref(false);

const error = ref('');
const loading = ref(false);
const clientSecret = ref('');

async function fetchClientSecret() {
    try {
        const response = await fetch('/api/create-payment-intent', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({ amount: 1000 }),
        });
        if (!response.ok) throw new Error('Network error');
        const data = await response.json();
        clientSecret.value = data.clientSecret;
    } catch {
        error.value = 'Failed to fetch client secret.';
    }
}

onMounted(async () => {
    await fetchClientSecret();
    if (!clientSecret.value) return;
    stripe.value = await loadStripe('pk_test_9aKCqOqHF5WbutLIVV0MZNqG00KMDpU2dh');
    elements.value = stripe.value!.elements({ clientSecret: clientSecret.value });

    paymentElement.value = elements.value.create('payment');
    paymentElement.value.mount(paymentElementMount.value!);

    paymentRequest.value = stripe.value!.paymentRequest({
        country: 'US',
        currency: 'usd',
        total: {
            label: 'Subscription',
            amount: 1000,
        },
        requestPayerName: true,
        requestPayerEmail: true,
    });

    paymentRequest.value.canMakePayment().then((result: any) => {
        if (result) {
            showPrButton.value = true;
            prButton.value = elements.value.create('paymentRequestButton', {
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
        } else {
            showPrButton.value = false;
        }
    });
});

onBeforeUnmount(() => {
    if (paymentElement.value) paymentElement.value.unmount();
    if (prButton.value) prButton.value.unmount();
});

async function submitSubscription() {
    loading.value = true;
    error.value = '';
    if (!stripe.value || !elements.value) {
        error.value = 'Stripe not loaded.';
        loading.value = false;
        return;
    }
    const { error: stripeError } = await stripe.value.confirmPayment({
        elements: elements.value,
        confirmParams: {
            return_url: window.location.origin + '/subscription/complete',
        },
        redirect: 'if_required',
    });
    if (stripeError) {
        error.value = stripeError.message || 'Payment error.';
        loading.value = false;
        return;
    }
    loading.value = false;
}
</script>

<template>
    <MyLayout>
        <main>
            <section class="mx-auto my-10 w-1/2 rounded bg-white p-8 text-gray-900 shadow" aria-labelledby="subscription-heading">
                <h1 id="subscription-heading" class="mb-6 text-center text-3xl font-bold">Subscribe</h1>
                <form @submit.prevent="submitSubscription" class="flex flex-col gap-4" role="form">
                    <div>
                        <label class="mb-1 block font-medium text-gray-700">Payment Details</label>
                        <div ref="paymentElementMount" class="w-full rounded border bg-gray-50 px-4 py-2"></div>
                    </div>
                    <button
                        type="submit"
                        :disabled="loading"
                        class="rounded-xl bg-gradient-to-r from-teal-500 to-blue-500 px-4 py-2 text-lg font-bold text-white shadow transition hover:cursor-pointer hover:from-teal-600 hover:to-blue-600 focus:ring-2 focus:ring-blue-700 focus:outline-none"
                    >
                        {{ loading ? 'Processing...' : 'Start 1 Week Free Trial' }}
                    </button>
                    <p v-if="error" class="mt-2 text-center text-red-600">{{ error }}</p>
                </form>
                <div class="mt-6">
                    <div v-if="showPrButton" ref="prButtonMount"></div>
                    <div v-else class="text-center text-sm text-gray-500">Apple Pay / Google Pay not available on this device or browser.</div>
                </div>
                <p class="mt-4 text-center text-gray-600">You will not be charged until your free trial ends.</p>
            </section>
        </main>
    </MyLayout>
</template>