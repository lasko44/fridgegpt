<template>
    <form @submit.prevent="handleSubmit" class="max-w-md mx-auto p-8 bg-white rounded-xl shadow-lg">
        <label class="block mb-2 text-lg font-semibold text-gray-700">Card Details</label>
        <div id="card-element" class="mb-6 border border-gray-300 rounded-lg bg-gray-50 p-4"></div>
        <button
            type="submit"
            :disabled="loading"
            class="w-full bg-teal-600 text-white font-bold py-3 rounded-lg hover:bg-teal-700 transition disabled:opacity-50"
        >
            {{ loading ? 'Processing...' : 'Subscribe' }}
        </button>
        <p v-if="error" class="mt-4 text-red-600 text-center">{{ error }}</p>
    </form>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { loadStripe } from '@stripe/stripe-js';

const stripePromise = loadStripe('pk_test_YourPublicKey'); // Replace with your Stripe public key
const loading = ref(false);
const error = ref('');

let stripe, elements, card;

onMounted(async () => {
    stripe = await stripePromise;
    elements = stripe.elements();
    card = elements.create('card');
    card.mount('#card-element');
});

async function handleSubmit() {
    loading.value = true;
    error.value = '';
    const { error: stripeError, paymentMethod } = await stripe.createPaymentMethod({
        type: 'card',
        card,
    });
    if (stripeError) {
        error.value = stripeError.message;
        loading.value = false;
        return;
    }
    // Send paymentMethod.id to your Laravel backend via AJAX or Inertia
    loading.value = false;
}
</script>