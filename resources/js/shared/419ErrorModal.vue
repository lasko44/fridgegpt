<script setup lang="ts">
import { ref } from 'vue'
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { AlertCircle } from 'lucide-vue-next'

const emit = defineEmits(['close'])
const open = ref(true)

function handleOpenChange(value: boolean) {
    if (!value) {
        emit('close')
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="handleOpenChange">
        <DialogContent class="max-w-md">
            <DialogHeader class="text-center">
                <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
                    <AlertCircle class="h-6 w-6 text-red-600" />
                </div>
                <DialogTitle class="text-2xl font-semibold text-red-600">
                    Oops
                </DialogTitle>
                <DialogDescription class="text-gray-700">
                    Something went wrong. Try reloading the page.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="justify-center">
                <Button @click="emit('close')" variant="default">
                    Close
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>