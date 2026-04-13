// resources/js/stores/toastStore.ts
import { ref } from 'vue';

export const showToast = ref(false);
export const toastType = ref('info');
export const toastTitle = ref('');
export const toastMessage = ref('');
export const toastHref = ref<string | null>(null);
let timer: number | undefined;

export function triggerToast({
    type = 'info',
    title = '',
    message = '',
    duration = 2500,
    href = null as string | null,
}) {
    toastType.value = type;
    toastTitle.value = title;
    toastMessage.value = message;
    toastHref.value = href;
    showToast.value = true;
    clearTimeout(timer);
    timer = window.setTimeout(() => {
        showToast.value = false;
    }, duration);
}

export function dismissToast() {
    showToast.value = false;
    clearTimeout(timer);
}
