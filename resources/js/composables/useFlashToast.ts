import { watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { triggerToast } from '@/stores/toastStore';
import type { Flash } from '@/interfaces/flash';

export function useFlashToast() {
    const page = usePage();

    watch(
        () => (page.props as { flash?: Flash }).flash,
        (flash: Flash | undefined) => {
           if (flash?.success) {
               triggerToast?.({
                   type: 'success',
                   title: 'Success',
                   message: flash.success,
                   duration: 5000,
               });
           }
           if (flash?.error) {
               triggerToast?.({
                   type: 'error',
                   title: 'Error',
                   message: flash.error,
                   duration: 5000,
               });
           }
           if (flash?.info) {
               triggerToast?.({
                   type: 'info',
                   title: 'Info',
                   message: flash.info,
                   duration: 5000,
               });
           }
        },
        { immediate: true },
    );
}