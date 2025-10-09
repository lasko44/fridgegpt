// resources/js/stores/toastStore.ts
import { ref } from 'vue';

export const showToast = ref(false);
export const toastType = ref('info');
export const toastTitle = ref('');
export const toastMessage = ref('');
let timer: number | undefined;

export function triggerToast({ type = 'info', title = '', message = '', duration = 2500 }) {
    toastType.value = type;
    toastTitle.value = title;
    toastMessage.value = message;
    showToast.value = true;
    clearTimeout(timer);
    timer = window.setTimeout(() => {
        showToast.value = false;
    }, duration);
}
