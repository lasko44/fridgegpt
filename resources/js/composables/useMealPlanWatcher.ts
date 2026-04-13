import { onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { triggerToast } from '@/stores/toastStore';

interface PendingPlan {
    uuid: string;
    name: string;
    status: 'draft' | 'generating' | 'complete' | 'failed';
    updated_at: string;
}

const STORAGE_KEY = 'fridgegpt:pending-meal-plans';
const POLL_INTERVAL_MS = 5000;

/**
 * Globally watches the user's meal plans and shows a toast when one finishes.
 * Mount this on the app layout so it runs on every page.
 */
export function useMealPlanWatcher() {
    let pollHandle: ReturnType<typeof setInterval> | null = null;

    function loadTrackedStatuses(): Record<string, string> {
        try {
            return JSON.parse(sessionStorage.getItem(STORAGE_KEY) || '{}');
        } catch {
            return {};
        }
    }

    function saveTrackedStatuses(map: Record<string, string>): void {
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify(map));
    }

    async function check(): Promise<void> {
        try {
            const { data } = await axios.get('/meal-plans/pending', {
                headers: { Accept: 'application/json' },
            });
            const plans: PendingPlan[] = data?.plans ?? [];
            const tracked = loadTrackedStatuses();
            const updated: Record<string, string> = {};

            for (const plan of plans) {
                const prev = tracked[plan.uuid];
                const wasGenerating = prev === 'generating' || prev === 'draft';

                if (plan.status === 'complete' && wasGenerating) {
                    triggerToast({
                        type: 'success',
                        title: 'Meal plan ready!',
                        message: `${plan.name} is ready to view.`,
                        duration: 8000,
                        href: `/meal-plans/${plan.uuid}`,
                    });
                } else if (plan.status === 'failed' && wasGenerating) {
                    triggerToast({
                        type: 'error',
                        title: 'Meal plan failed',
                        message: `Generation failed for ${plan.name}.`,
                        duration: 8000,
                        href: `/meal-plans/${plan.uuid}`,
                    });
                }

                // Only keep tracking plans still in progress
                if (plan.status === 'generating' || plan.status === 'draft') {
                    updated[plan.uuid] = plan.status;
                }
            }

            saveTrackedStatuses(updated);

            // Stop polling if nothing is in progress
            if (Object.keys(updated).length === 0) {
                stop();
            }
        } catch {
            // Silent fail — try again next interval
        }
    }

    function start(): void {
        if (pollHandle) return;
        check();
        pollHandle = setInterval(check, POLL_INTERVAL_MS);
    }

    function stop(): void {
        if (pollHandle) {
            clearInterval(pollHandle);
            pollHandle = null;
        }
    }

    onMounted(start);
    onUnmounted(stop);
}
