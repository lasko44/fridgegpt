<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { loadStripe, Stripe, StripePaymentElement, StripePaymentRequestButtonElement } from '@stripe/stripe-js';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import MyLayout from '../layouts/MyLayout.vue';
import Subscribed from '../shared/Subscribed.vue';

const page = usePage();

const stripe = ref<Stripe | null>(null);
const elements = ref<any>(null);
const paymentElement = ref<StripePaymentElement | null>(null);
const paymentElementMount = ref<HTMLDivElement | null>(null);

const prButton = ref<StripePaymentRequestButtonElement | null>(null);
const prButtonMount = ref<HTMLDivElement | null>(null);
const paymentRequest = ref<any>(null);
const showPrButton = ref(false);

const clientSecret = ref('');
const error = ref('');

const form = useForm({
    payment_method: '',
});

const isSubscribed = !!page.props.auth?.user?.subscribed; // Adjust if needed

// Fetch SetupIntent client secret for subscriptions
async function fetchSetupIntentSecret() {
    try {
        const response = await fetch('/api/create-setup-intent', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                user: page.props.auth?.user?.id,
            }),
            credentials: 'same-origin',
        });
        if (!response.ok) throw new Error('Network error');
        const data = await response.json();
        clientSecret.value = data.clientSecret;
    } catch {
        error.value = 'Failed to fetch setup intent secret.';
    }
}

onMounted(async () => {
    if (isSubscribed) return;
    await fetchSetupIntentSecret();
    if (!clientSecret.value) return;
    stripe.value = await loadStripe('pk_test_51RmxzdPDvw13epAC8BODuxy0o8MOpgck8PGaW4OsnC38jhl22aYfhNQXq4myubJjymme1ZZiBXUizSqAjWdagSsj00n8a4b6Ac');
    elements.value = stripe.value?.elements({ clientSecret: clientSecret.value });

    if (elements.value && paymentElementMount.value) {
        paymentElement.value = elements.value.create('payment');
        paymentElement.value.mount(paymentElementMount.value);
    }

    paymentRequest.value = stripe.value?.paymentRequest({
        country: 'US',
        currency: 'usd',
        total: {
            label: 'Subscription',
            amount: 1000,
        },
        requestPayerName: true,
        requestPayerEmail: true,
    });

    paymentRequest.value?.canMakePayment().then((result: any) => {
        if (result) {
            showPrButton.value = true;
            if (elements.value && prButtonMount.value) {
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
                prButton.value.mount(prButtonMount.value);
            }
        } else {
            showPrButton.value = false;
        }
    });
});

onBeforeUnmount(() => {
    if (paymentElement.value) paymentElement.value.unmount();
    if (prButton.value) prButton.value.unmount();
});

// Use confirmSetup for subscriptions
async function submitSubscription() {
    error.value = '';
    if (!stripe.value || !elements.value) {
        error.value = 'Stripe not loaded.';
        return;
    }
    const { setupIntent, error: setupError } = await stripe.value.confirmSetup({
        elements: elements.value,
        confirmParams: {
            return_url: window.location.href,
        },
        redirect: 'if_required',
    });
    if (setupError) {
        error.value = setupError.message || 'Payment error.';
        return;
    }
    form.payment_method = setupIntent?.payment_method ?? '';
    form.post('/subscription', {
        onError: (errors) => {
            error.value = errors.payment_method || 'Failed to create subscription.';
        },
        onSuccess: () => {
        },
        preserveScroll: true,
    });
}
</script>

<template>
    <MyLayout>
        <main>
            <section v-if="isSubscribed">
                <Subscribed />
            </section>
            <section v-else class="mx-auto my-10 w-1/2 rounded bg-white p-8 text-gray-900 shadow" aria-labelledby="subscription-heading">
                <h1 id="subscription-heading" class="mb-4 text-center text-3xl font-bold">Subscribe</h1>
                <div class="mb-6 flex justify-center">
                    <div class="rounded-full bg-gradient-to-r from-teal-500 to-blue-500 px-6 py-2 text-2xl font-extrabold text-yellow-300 shadow-lg">
                        $3.99<span class="ml-1 text-base font-semibold text-white">/month</span>
                    </div>
                </div>
                <form @submit.prevent="submitSubscription" class="flex flex-col gap-4" role="form">
                    <div>
                        <label class="mb-1 block font-medium text-gray-700">Payment Details</label>
                        <div ref="paymentElementMount" class="w-full rounded border bg-gray-50 px-4 py-2"></div>
                    </div>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-xl bg-gradient-to-r from-teal-500 to-blue-500 px-4 py-2 text-lg font-bold text-white shadow transition hover:cursor-pointer hover:from-teal-600 hover:to-blue-600 focus:ring-2 focus:ring-blue-700 focus:outline-none"
                    >
                        {{ form.processing ? 'Processing...' : 'Start 1 Week Free Trial' }}
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