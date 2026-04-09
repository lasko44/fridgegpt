<script setup lang="ts">
import { computed, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Check } from 'lucide-vue-next'

const page = usePage()
const props = defineProps<{ isLoggedIn: boolean }>()
const emit = defineEmits(['close'])

const open = ref(true)
const guestLimitError = computed(() => page.props?.errors?.error)

function handleAction() {
    window.location.href = props.isLoggedIn ? '/subscription/create' : '/login'
}

function handleOpenChange(value: boolean) {
    if (!value) {
        emit('close')
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="handleOpenChange">
        <DialogContent class="max-w-2xl p-0 overflow-hidden border-0">
            <div class="bg-gray-900 p-10 flex flex-col items-center text-white">
                <DialogHeader class="text-center">
                    <DialogTitle class="text-4xl font-extrabold mb-2 drop-shadow-lg text-white">
                        Go Premium
                    </DialogTitle>
                    <DialogDescription class="text-white">
                        <div class="mb-2 text-3xl font-bold text-emerald-400 drop-shadow-lg">
                            $3.99<span class="text-white text-lg font-semibold">/month</span>
                        </div>
                    </DialogDescription>
                </DialogHeader>

                <p class="mb-6 text-center text-lg font-semibold drop-shadow md:text-xl">
                    Unlock all features with Premium and enjoy a <span class="font-extrabold text-emerald-400">free 1-week trial</span>!
                </p>

                <ul class="mb-6 w-full text-base font-bold md:text-lg space-y-2">
                    <li class="flex items-center">
                        <Check class="mr-2 h-5 w-5 text-emerald-500 drop-shadow" />
                        <span class="drop-shadow">Unlimited recipes</span>
                    </li>
                    <li class="flex items-center">
                        <Check class="mr-2 h-5 w-5 text-emerald-500 drop-shadow" />
                        <span class="drop-shadow">Favorite recipes</span>
                    </li>
                    <li class="flex items-center">
                        <Check class="mr-2 h-5 w-5 text-emerald-500 drop-shadow" />
                        <span class="drop-shadow">Recipe variations</span>
                    </li>
                    <li class="flex items-center">
                        <Check class="mr-2 h-5 w-5 text-emerald-500 drop-shadow" />
                        <span class="drop-shadow">Nutritional facts</span>
                    </li>
                </ul>

                <p v-if="guestLimitError" class="text-gray-100 text-lg mb-4 font-semibold bg-white/20 px-4 py-2 rounded">
                    {{ guestLimitError }}
                </p>

                <Button
                    @click="handleAction"
                    class="w-full py-3 px-6 rounded-full bg-white text-emerald-600 font-bold text-lg shadow transition hover:bg-gray-100"
                    variant="secondary"
                    size="lg"
                >
                    {{ props.isLoggedIn ? 'Upgrade Now!' : 'Go to Login' }}
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>