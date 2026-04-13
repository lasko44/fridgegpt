import { onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { triggerToast } from '@/stores/toastStore';

interface Notification {
    id: string;
    type: string;
    title: string;
    body: string;
    href: string;
    created_at: number;
}

const SEEN_KEY = 'fridgegpt:seen-notifications:';
const SINCE_KEY = 'fridgegpt:notification-since:';
const POLL_INTERVAL_MS = 6000;

/**
 * Polls a notification feed endpoint and shows toasts for unseen notifications.
 * Persists seen IDs and last poll time in localStorage so it survives page navigations.
 */
export function useNotificationWatcher(endpoint: string, scope = 'default') {
    const seenKey = SEEN_KEY + scope;
    const sinceKey = SINCE_KEY + scope;
    let pollHandle: ReturnType<typeof setInterval> | null = null;

    function loadSeen(): Set<string> {
        try {
            const raw = localStorage.getItem(seenKey);
            return raw ? new Set(JSON.parse(raw)) : new Set();
        } catch {
            return new Set();
        }
    }

    function saveSeen(seen: Set<string>): void {
        const ids = Array.from(seen).slice(-200);
        localStorage.setItem(seenKey, JSON.stringify(ids));
    }

    function loadSince(): number {
        const raw = localStorage.getItem(sinceKey);
        if (raw) return parseInt(raw, 10) || 0;
        // First-ever load: only watch from now forward
        const now = Math.floor(Date.now() / 1000);
        localStorage.setItem(sinceKey, String(now));
        return now;
    }

    function saveSince(since: number): void {
        localStorage.setItem(sinceKey, String(since));
    }

    function toastTypeFor(type: string): 'info' | 'success' {
        if (type === 'support_inbound' || type === 'support_reply') return 'info';
        return 'success';
    }

    async function check(): Promise<void> {
        try {
            const since = loadSince();
            const url = `${endpoint}?since=${since}`;
            const { data } = await axios.get(url, { headers: { Accept: 'application/json' } });
            const notifications: Notification[] = data?.notifications ?? [];
            const seen = loadSeen();

            const fresh = notifications
                .filter((n) => !seen.has(n.id))
                .sort((a, b) => a.created_at - b.created_at);

            for (const n of fresh) {
                triggerToast({
                    type: toastTypeFor(n.type),
                    title: n.title,
                    message: n.body.length > 120 ? n.body.slice(0, 117) + '…' : n.body,
                    duration: 8000,
                    href: n.href,
                });
                seen.add(n.id);
            }

            if (fresh.length > 0) {
                saveSeen(seen);
            }

            saveSince(Math.floor(Date.now() / 1000));
        } catch {
            // Silent fail — try again next interval
        }
    }

    onMounted(() => {
        check(); // Run once immediately
        pollHandle = setInterval(check, POLL_INTERVAL_MS);
    });

    onUnmounted(() => {
        if (pollHandle) clearInterval(pollHandle);
    });
}
