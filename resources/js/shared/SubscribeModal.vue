<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()
const props = defineProps<{ isLoggedIn: boolean }>()
const emit = defineEmits(['close'])

const guestLimitError = computed(() => page.props?.errors?.guest_limit)

function handleAction() {
    window.location.href = props.isLoggedIn ? '/subscription/create' : '/login'
}

function handleClose() {
    emit('close')
}
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60">
        <div class="w-full max-w-2xl rounded-2xl shadow-2xl p-0 overflow-hidden relative">
            <!-- Close button -->
            <button
                @click="handleClose"
                class="absolute top-4 right-4 hover:cursor-pointer text-white text-2xl font-bold hover:text-gray-300 focus:outline-none"
                aria-label="Close"
            >
                &times;
            </button>
            <div class="bg-gradient-to-r from-teal-500 via-cyan-500 to-blue-500 p-10 flex flex-col items-center text-white">
                <h2 class="text-4xl font-extrabold mb-2 drop-shadow-lg">Go Premium</h2>
                <div class="mb-2 text-3xl font-bold text-yellow-300 drop-shadow-lg">
                    $3.99<span class="text-white text-lg font-semibold">/month</span>
                </div>
                <p class="mb-6 text-center text-lg font-semibold drop-shadow md:text-xl">
                    Unlock all features with Premium and enjoy a <span class="font-extrabold text-yellow-300">free 1-week trial</span>!
                </p>
                <ul class="mb-6 w-full text-base font-bold md:text-lg">
                    <li class="mb-2 flex items-center">
                        <span class="mr-2 text-green-300 drop-shadow">&#10003;</span>
                        <span class="drop-shadow">Unlimited recipes</span>
                    </li>
                    <li class="mb-2 flex items-center">
                        <span class="mr-2 text-green-300 drop-shadow">&#10003;</span>
                        <span class="drop-shadow">Favorite recipes</span>
                    </li>
                    <li class="mb-2 flex items-center">
                        <span class="mr-2 text-green-300 drop-shadow">&#10003;</span>
                        <span class="drop-shadow">Recipe variations</span>
                    </li>
                    <li class="mb-2 flex items-center">
                        <span class="mr-2 text-green-300 drop-shadow">&#10003;</span>
                        <span class="drop-shadow">Nutritional facts</span>
                    </li>
                </ul>
                <p v-if="guestLimitError" class="text-teal-900 text-lg mb-4 font-semibold">{{ guestLimitError }}</p>
                <button
                    @click="handleAction"
                    class="w-full py-3 px-6 hover:cursor-pointer rounded-full bg-white text-teal-700 font-bold text-lg shadow transition hover:bg-blue-100 focus:ring-2 focus:ring-white focus:outline-none"
                >
                    {{ props.isLoggedIn ? 'Upgrade Now!' : 'Go to Login' }}
                </button>
            </div>
        </div>
    </div>
</template>