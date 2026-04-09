<script setup lang="ts">
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import axios from 'axios'

const emit = defineEmits<{
    (e: 'add-ingredient', name: string): void
    (e: 'close'): void
}>()

const barcode = ref('')
const loading = ref(false)
const error = ref('')
const mode = ref<'camera' | 'manual'>('camera')
const cameraError = ref('')
const product = ref<{
    product_name?: string
    name?: string
    image_url?: string | null
    nutrition?: { calories?: number; protein?: number; carbs?: number; fat?: number }
} | null>(null)

let scanner: any = null

onMounted(async () => {
    await nextTick()
    startCamera()
})

onUnmounted(() => {
    stopCamera()
})

async function startCamera() {
    cameraError.value = ''
    try {
        const { Html5Qrcode } = await import('html5-qrcode')
        scanner = new Html5Qrcode('barcode-camera-feed')

        await scanner.start(
            { facingMode: 'environment' },
            {
                fps: 10,
                qrbox: { width: 280, height: 120 },
                aspectRatio: 1.5,
            },
            (decodedText: string) => {
                // Barcode detected
                barcode.value = decodedText
                stopCamera()
                lookup()
            },
            () => {
                // Scan failure — ignore, keep scanning
            }
        )
    } catch (err: any) {
        cameraError.value = 'Camera not available. Use manual entry instead.'
        mode.value = 'manual'
    }
}

function stopCamera() {
    try {
        if (scanner && scanner.isScanning) {
            scanner.stop().catch(() => {})
        }
    } catch {
        // Ignore cleanup errors
    }
}

function switchMode(newMode: 'camera' | 'manual') {
    if (newMode === mode.value) return
    stopCamera()
    mode.value = newMode
    if (newMode === 'camera') {
        nextTick(() => startCamera())
    }
}

async function lookup() {
    const code = barcode.value.trim()
    if (!code) return

    loading.value = true
    error.value = ''
    product.value = null

    try {
        const { data } = await axios.post('/api/v1/barcode/lookup', { barcode: code })
        product.value = data
    } catch {
        error.value = 'Product not found. Try a different barcode or enter manually.'
    } finally {
        loading.value = false
    }
}

function addIngredient() {
    const name = product.value?.product_name || product.value?.name
    if (name) {
        emit('add-ingredient', name)
        close()
    }
}

function close() {
    stopCamera()
    barcode.value = ''
    product.value = null
    error.value = ''
    loading.value = false
    emit('close')
}
</script>

<template>
    <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        aria-label="Barcode scanner"
        @click.self="close"
        @keydown.escape="close"
    >
        <div class="relative w-full max-w-md mx-4 rounded-2xl bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] shadow-xl overflow-hidden">
            <!-- Header -->
            <div class="flex items-center justify-between px-5 py-3 border-b border-[#EDE5DD] dark:border-[#3D3D39]">
                <h2 class="font-serif text-lg font-semibold text-[#3A2520] dark:text-[#E8E0D4]">Scan Barcode</h2>
                <button
                    @click="close"
                    class="h-8 w-8 rounded-full flex items-center justify-center text-[#6B5C55] hover:bg-[#FBF5F0] dark:hover:bg-[#2E2E2B] transition"
                    aria-label="Close"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Body -->
            <div class="px-5 py-4 space-y-4">
                <!-- Camera view -->
                <div v-if="!product && !loading">
                    <div id="barcode-camera-feed" class="w-full rounded-xl overflow-hidden bg-black" style="min-height: 200px;"></div>
                    <p v-if="cameraError" class="mt-2 text-xs text-red-500">{{ cameraError }}</p>
                    <p v-else class="mt-2 text-xs text-[#6B5C55] dark:text-[#C9B8A6] text-center">Point your camera at a barcode</p>
                </div>

                <!-- Manual entry — always visible below camera -->
                <div v-if="!product && !loading" class="relative">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="flex-1 h-px bg-[#EDE5DD] dark:bg-[#3D3D39]"></div>
                        <span class="text-xs text-[#6B5C55] dark:text-[#C9B8A6]">or enter manually</span>
                        <div class="flex-1 h-px bg-[#EDE5DD] dark:bg-[#3D3D39]"></div>
                    </div>
                </div>

                <form v-if="!product && !loading" @submit.prevent="lookup" class="flex gap-2">
                    <input
                        v-model="barcode"
                        type="text"
                        inputmode="numeric"
                        placeholder="Enter barcode number"
                        class="flex-1 rounded-xl px-4 py-2.5 border border-[#EDE5DD] dark:border-[#3D3D39] bg-[#FBF5F0] dark:bg-[#2E2E2B] text-[#3A2520] dark:text-[#E8E0D4] placeholder-[#B0A196] focus:outline-none focus:ring-2 focus:ring-[#C27B5B] text-sm"
                        :disabled="loading"
                        aria-label="Barcode number"
                    />
                    <button
                        type="submit"
                        :disabled="!barcode.trim() || loading"
                        class="px-5 py-2.5 rounded-xl bg-[#C27B5B] text-white text-sm font-semibold hover:bg-[#A8664A] disabled:opacity-40 transition"
                    >
                        {{ loading ? 'Looking up...' : 'Look Up' }}
                    </button>
                </form>

                <!-- Loading -->
                <div v-if="loading" class="flex items-center justify-center py-6">
                    <svg class="animate-spin h-6 w-6 text-[#C27B5B]" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    <span class="ml-2 text-sm text-[#6B5C55]">Looking up product...</span>
                </div>

                <!-- Error -->
                <div v-if="error" class="rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800/40 px-4 py-3 text-sm text-red-700 dark:text-red-300" role="alert">
                    {{ error }}
                    <button @click="error = ''; product = null; barcode = ''" class="block mt-1 text-xs underline">Try again</button>
                </div>

                <!-- Product result -->
                <div v-if="product" class="rounded-xl border border-[#EDE5DD] dark:border-[#3D3D39] overflow-hidden">
                    <div class="flex items-start gap-4 p-4">
                        <img
                            v-if="product.image_url"
                            :src="product.image_url"
                            :alt="product.product_name || product.name"
                            class="w-16 h-16 rounded-lg object-cover border border-[#EDE5DD] shrink-0"
                        />
                        <div class="flex-1 min-w-0">
                            <h3 class="font-semibold text-[#3A2520] dark:text-[#E8E0D4] text-sm">{{ product.product_name || product.name }}</h3>
                            <div v-if="product.nutrition" class="flex flex-wrap gap-3 mt-1.5 text-xs text-[#6B5C55] dark:text-[#C9B8A6]">
                                <span v-if="product.nutrition.calories != null">{{ product.nutrition.calories }} cal</span>
                                <span v-if="product.nutrition.protein != null">{{ product.nutrition.protein }}g protein</span>
                                <span v-if="product.nutrition.carbs != null">{{ product.nutrition.carbs }}g carbs</span>
                                <span v-if="product.nutrition.fat != null">{{ product.nutrition.fat }}g fat</span>
                            </div>
                            <p class="text-xs text-[#B0A196] mt-1">per 100g</p>
                        </div>
                    </div>
                    <div class="px-4 pb-4 flex gap-2">
                        <button
                            @click="addIngredient"
                            class="flex-1 px-4 py-2.5 rounded-xl bg-[#C27B5B] text-white text-sm font-semibold hover:bg-[#A8664A] transition"
                        >
                            Add to Ingredients
                        </button>
                        <button
                            @click="product = null; barcode = ''; error = ''; mode === 'camera' && nextTick(() => startCamera())"
                            class="px-4 py-2.5 rounded-xl border border-[#EDE5DD] dark:border-[#3D3D39] text-sm text-[#6B5C55] hover:bg-[#FBF5F0] transition"
                        >
                            Scan Another
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
