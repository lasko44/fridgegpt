<script setup lang="ts">
import { useHead } from '@vueuse/head';
import MyLayout from '@/layouts/MyLayout.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { reactive, ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { triggerToast } from '@/stores/toastStore';
import ConfirmDialog from '@/shared/ConfirmDialog.vue';

interface GroceryItem {
    name: string;
    quantity: string;
    unit: string;
    estimated_price_cents: number | null;
    kroger_name?: string | null;
    kroger_price?: number | null;
    kroger_size?: string | null;
}

interface MealSlot {
    meal_type: string;
    recipe_name: string;
    recipe_slug: string | null;
    calories: number | null;
}

interface DaySlots {
    day_number: number;
    day_label: string;
    meals: MealSlot[];
}

interface NutritionSummary {
    calories: number;
    protein: number;
    carbs: number;
    fat: number;
    targets: {
        calories: number | null;
        protein: number | null;
        carbs: number | null;
        fat: number | null;
    };
}

interface MealPlan {
    uuid: string;
    name: string;
    status: 'draft' | 'generating' | 'complete' | 'failed';
    days: number;
    meals_per_day: string[];
    budget_cents: number | null;
    grocery_total_cents: number | null;
    tokens_spent: number;
    created_at: string;
    slots: DaySlots[];
    grocery_list: GroceryItem[];
    estimated_total_price: number | null;
}

const props = defineProps<{
    plan: MealPlan;
    dailyNutrition: Record<number, NutritionSummary>;
    tokenBalance: number;
    userZipCode?: string | null;
}>();

useHead({ title: `${props.plan.name} | FridgeGPT` });

const statusColors: Record<string, string> = {
    draft: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    generating: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    complete: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
    failed: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
};

const statusLabels: Record<string, string> = {
    draft: 'Generating',
    generating: 'Generating',
    complete: 'Ready',
    failed: 'Failed',
};

const checkedGroceryItems = ref<Set<number>>(new Set());
const showDeleteConfirm = ref(false);

function deletePlan() {
    router.delete(`/meal-plans/${props.plan.uuid}`, {
        onSuccess: () => {
            showDeleteConfirm.value = false;
        },
    });
}
const expandedDays = reactive<Record<number, boolean>>({});
const groceryExpanded = ref(false);

function toggleDay(dayNumber: number) {
    expandedDays[dayNumber] = !expandedDays[dayNumber];
}

function isDayExpanded(dayNumber: number): boolean {
    return !!expandedDays[dayNumber];
}

function expandAll() {
    (props.plan.slots ?? []).forEach((d: any) => {
        expandedDays[d.day_number] = true;
    });
    groceryExpanded.value = true;
}

function collapseAll() {
    Object.keys(expandedDays).forEach((k) => {
        expandedDays[Number(k)] = false;
    });
    groceryExpanded.value = false;
}

function toggleGroceryItem(idx: number) {
    const copy = new Set(checkedGroceryItems.value);
    copy.has(idx) ? copy.delete(idx) : copy.add(idx);
    checkedGroceryItems.value = copy;
}

function swapMeal(dayNumber: number, mealType: string) {
    router.post(route('meal-plans.swap', { meal_plan: props.plan.uuid }), {
        day_number: dayNumber,
        meal_type: mealType,
    }, {
        preserveScroll: true,
    });
}

function printGroceryList() {
    window.print();
}

function nutritionColorClass(actual: number, target: number | null): string {
    if (target === null || target === 0) return 'text-[#3A2520] dark:text-[#F3EDE6]';
    const ratio = Math.abs(actual - target) / target;
    if (ratio <= 0.10) return 'text-emerald-600 dark:text-emerald-400';
    if (ratio <= 0.25) return 'text-amber-600 dark:text-amber-400';
    return 'text-red-600 dark:text-red-400';
}

// Checkout total — what you'll actually pay buying full products at the store
const checkoutTotal = computed(() => {
    const items = props.plan.grocery_list ?? [];
    return items.reduce((sum: number, item: any) => {
        return sum + ((item.kroger_price || item.estimated_price_cents || 0) / 100);
    }, 0);
});

// Recipe cost — what the actual portions used are worth
const recipeCostTotal = computed(() => {
    return (props.plan.grocery_list ?? []).reduce(
        (sum: number, item: any) => sum + ((item.estimated_price_cents || 0) / 100),
        0,
    );
});

const hasKrogerData = computed(() => (props.plan.grocery_list ?? []).some((i: any) => i.kroger_price != null));
const itemCount = computed(() => (props.plan.grocery_list ?? []).length);

const createdAtFormatted = computed(() => {
    return new Date(props.plan.created_at).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
});

// Kroger pricing
const zipCode = ref(props.userZipCode ?? '');
const stores = ref<{ locationId: string; name: string; address: string; chain: string }[]>([]);
const selectedStore = ref('');
const loadingStores = ref(false);
const loadingPrices = ref(false);
// Removed — replaced by hasKrogerData in the totals section

async function findStores() {
    const zip = zipCode.value.trim();
    if (! /^\d{5}$/.test(zip)) {
        triggerToast({ type: 'error', title: 'Invalid zip code', message: 'Enter a 5-digit US zip code.', duration: 3000 });
        return;
    }
    loadingStores.value = true;
    try {
        const response = await fetch(`/grocery/stores?zip_code=${zip}`, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const data = await response.json();
        stores.value = data.stores ?? [];
        if (stores.value.length > 0) {
            selectedStore.value = stores.value[0].locationId;
        } else {
            triggerToast({ type: 'info', title: 'No stores found', message: 'Try a different zip code.', duration: 4000 });
        }
    } catch (err: any) {
        triggerToast({ type: 'error', title: 'Could not find stores', message: err?.response?.data?.message || 'Check your zip code and try again.', duration: 4000 });
    } finally {
        loadingStores.value = false;
    }
}

async function getRealPrices() {
    if (! selectedStore.value) return;
    loadingPrices.value = true;
    const storeName = stores.value.find((s) => s.locationId === selectedStore.value)?.name ?? 'Kroger';
    try {
        const csrfToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
        const response = await fetch('/grocery/price', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? ''),
            },
            body: JSON.stringify({
                meal_plan_uuid: props.plan.uuid,
                location_id: selectedStore.value,
                store_name: storeName,
            }),
        });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        // Reload the page to pick up the new grocery_list with Kroger prices
        router.reload({ only: ['plan'], preserveScroll: true });
        triggerToast({ type: 'success', title: 'Prices updated!', message: `Real prices from ${storeName}`, duration: 5000 });
    } catch {
        triggerToast({ type: 'error', title: 'Pricing failed', message: 'Could not fetch prices. Try again.', duration: 4000 });
    } finally {
        loadingPrices.value = false;
    }
}

// Auto-refresh while the plan is generating
let pollInterval: ReturnType<typeof setInterval> | null = null;
const lastSeenStatus = ref<string>(props.plan.status);

function startPolling() {
    if (pollInterval) return;
    pollInterval = setInterval(() => {
        router.reload({ only: ['plan', 'dailyNutrition'], preserveScroll: true });
    }, 3000);
}

function stopPolling() {
    if (pollInterval) {
        clearInterval(pollInterval);
        pollInterval = null;
    }
}

function totalRecipes(): number {
    return (props.plan.slots ?? []).reduce(
        (acc, day) => acc + (day.meals?.length ?? 0),
        0,
    );
}

function notifyComplete() {
    triggerToast({
        type: 'success',
        title: 'Meal plan ready!',
        message: `${props.plan.name} is all set with ${totalRecipes()} recipes.`,
        duration: 6000,
        href: `/meal-plans/${props.plan.uuid}`,
    });
}

function notifyFailed() {
    triggerToast({
        type: 'error',
        title: 'Generation failed',
        message: 'Something went wrong generating your meal plan. Please try again.',
        duration: 6000,
    });
}

onMounted(() => {
    if (props.plan.status === 'generating' || props.plan.status === 'draft') {
        startPolling();
    }
});

onUnmounted(stopPolling);

// Watch the status. Inertia partial reloads update props in place,
// so this watcher fires when the polled response changes the status.
watch(
    () => props.plan.status,
    (status) => {
        const wasGenerating =
            lastSeenStatus.value === 'generating' || lastSeenStatus.value === 'draft';

        if (status === 'complete' && wasGenerating) {
            stopPolling();
            notifyComplete();
            // Final reload after a moment to ensure Kroger prices are included
            setTimeout(() => {
                router.reload({ only: ['plan'], preserveScroll: true });
            }, 2000);
        } else if (status === 'failed' && wasGenerating) {
            stopPolling();
            notifyFailed();
        } else if (status === 'generating' || status === 'draft') {
            startPolling();
        }

        lastSeenStatus.value = status;
    },
    { immediate: false },
);
</script>

<template>
    <MyLayout>
        <div class="mx-auto max-w-4xl px-4 py-10 md:py-14">

            <!-- Back link -->
            <Link
                href="/meal-plans"
                class="inline-flex items-center gap-1.5 text-sm text-[#6B5C55] dark:text-[#C9B8A6] hover:text-[#C27B5B] dark:hover:text-[#C27B5B] transition mb-6"
                aria-label="Back to meal plans"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
                Back to meal plans
            </Link>

            <!-- Header -->
            <div class="mb-8">
                <div class="flex flex-wrap items-center gap-3 mb-2">
                    <h1 class="font-serif text-3xl md:text-4xl font-bold text-[#3A2520] dark:text-[#F3EDE6] tracking-tight">
                        {{ plan.name }}
                    </h1>
                    <span
                        class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium"
                        :class="statusColors[plan.status] || statusColors.draft"
                    >
                        {{ statusLabels[plan.status] || plan.status }}
                    </span>
                </div>
                <div class="flex flex-wrap items-center gap-3 text-sm text-[#6B5C55] dark:text-[#C9B8A6]">
                    <span>{{ createdAtFormatted }}</span>
                    <span aria-hidden="true">&middot;</span>
                    <span>{{ plan.days }} days</span>
                    <span aria-hidden="true">&middot;</span>
                    <span>{{ plan.tokens_spent }} tokens spent</span>
                    <span v-if="plan.budget_cents">
                        <span aria-hidden="true">&middot;</span>
                        Budget: ${{ (plan.budget_cents / 100).toFixed(0) }}
                        <template v-if="plan.grocery_total_cents">
                            &mdash; Est: ${{ (plan.grocery_total_cents / 100).toFixed(2) }}
                            <span v-if="plan.grocery_total_cents > plan.budget_cents" class="text-red-600 dark:text-red-400 font-semibold ml-1">over budget</span>
                            <span v-else class="text-[#7A8C5A] dark:text-emerald-400 font-semibold ml-1">under budget</span>
                        </template>
                    </span>
                </div>
            </div>

            <!-- Generating spinner -->
            <div
                v-if="plan.status === 'generating' || plan.status === 'draft'"
                class="flex flex-col items-center justify-center py-16 text-center"
                role="status"
                aria-live="polite"
            >
                <svg class="animate-spin h-10 w-10 text-[#C27B5B] mb-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                <p class="text-lg font-medium text-[#3A2520] dark:text-[#F3EDE6]">Cooking up your meal plan...</p>
                <p class="text-sm text-[#6B5C55] dark:text-[#C9B8A6] mt-1 max-w-md mx-auto">
                    This usually takes 2&ndash;4 minutes since we're generating a full recipe for every meal. Feel free to navigate away &mdash; your plan will be waiting in <a href="/meal-plans" class="text-[#C27B5B] hover:underline">Meal Plans</a> when it's ready.
                </p>
            </div>

            <!-- Day-by-day grid -->
            <div v-if="plan.status === 'complete' && plan.slots.length > 0" class="space-y-4 mb-10">
                <!-- Expand/Collapse all -->
                <div class="flex justify-end gap-3">
                    <button @click="expandAll" class="text-xs font-medium text-[#C27B5B] hover:underline">Expand all</button>
                    <button @click="collapseAll" class="text-xs font-medium text-[#6B5C55] dark:text-[#C9B8A6] hover:underline">Collapse all</button>
                </div>

                <section
                    v-for="day in plan.slots"
                    :key="day.day_number"
                    class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl overflow-hidden"
                    :aria-label="day.day_label"
                >
                    <!-- Day header (clickable to toggle) -->
                    <button
                        type="button"
                        class="w-full text-left px-6 py-4 bg-[#FBF5F0] dark:bg-[#2E2E2B] flex items-center justify-between"
                        :class="isDayExpanded(day.day_number) ? 'border-b border-[#EDE5DD] dark:border-[#3D3D39]' : ''"
                        @click="toggleDay(day.day_number)"
                        :aria-expanded="isDayExpanded(day.day_number)"
                    >
                        <h2 class="font-serif text-lg font-semibold text-[#3A2520] dark:text-[#F3EDE6]">
                            Day {{ day.day_number }} &mdash; {{ day.day_label }}
                        </h2>
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-[#6B5C55] dark:text-[#C9B8A6]">{{ day.meals.length }} meals</span>
                            <svg
                                class="h-5 w-5 text-[#6B5C55] dark:text-[#C9B8A6] transition-transform duration-200"
                                :class="isDayExpanded(day.day_number) ? 'rotate-180' : ''"
                                fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </button>

                    <!-- Meals list (collapsible) -->
                    <ul v-show="isDayExpanded(day.day_number)" class="divide-y divide-[#EDE5DD] dark:divide-[#3D3D39]/50">
                        <li
                            v-for="meal in day.meals"
                            :key="meal.meal_type"
                            class="flex items-center justify-between px-6 py-4"
                        >
                            <div class="flex-1 min-w-0">
                                <span class="block text-xs font-medium text-[#6B5C55] dark:text-[#C9B8A6] uppercase tracking-wider mb-0.5">
                                    {{ meal.meal_type }}
                                </span>
                                <span class="block text-sm font-medium text-[#3A2520] dark:text-[#F3EDE6] truncate">
                                    <Link
                                        v-if="meal.recipe_slug"
                                        :href="`/recipe/${meal.recipe_slug}`"
                                        class="hover:text-[#C27B5B] transition"
                                    >
                                        {{ meal.recipe_name }}
                                    </Link>
                                    <template v-else>{{ meal.recipe_name }}</template>
                                </span>
                                <span v-if="meal.calories" class="text-xs text-[#6B5C55] dark:text-[#C9B8A6]">
                                    {{ meal.calories }} cal
                                </span>
                            </div>
                            <button
                                type="button"
                                @click="swapMeal(day.day_number, meal.meal_type)"
                                class="ml-3 shrink-0 px-3 py-1.5 rounded-lg text-xs font-medium border border-[#EDE5DD] dark:border-[#3D3D39] text-[#6B5C55] dark:text-[#C9B8A6] hover:border-[#C27B5B] hover:text-[#C27B5B] dark:hover:text-[#C27B5B] transition focus:outline-none focus:ring-2 focus:ring-[#C27B5B]"
                                :aria-label="'Swap ' + meal.meal_type + ' on day ' + day.day_number"
                            >
                                Swap
                            </button>
                        </li>
                    </ul>

                    <!-- Daily nutrition summary -->
                    <div
                        v-if="isDayExpanded(day.day_number) && dailyNutrition?.[day.day_number]"
                        class="px-6 py-3 border-t border-[#EDE5DD] dark:border-[#3D3D39] bg-[#FBF5F0]/50 dark:bg-[#2E2E2B]/50"
                    >
                        <div class="flex flex-wrap gap-4 text-xs font-medium" role="list" aria-label="Daily nutrition summary">
                            <span
                                role="listitem"
                                :class="nutritionColorClass(dailyNutrition[day.day_number]?.calories ?? 0, dailyNutrition[day.day_number]?.targets?.calories ?? null)"
                            >
                                {{ dailyNutrition[day.day_number]?.calories ?? 0 }} cal
                                <template v-if="dailyNutrition[day.day_number]?.targets?.calories">
                                    / {{ dailyNutrition[day.day_number].targets.calories }}
                                </template>
                            </span>
                            <span
                                role="listitem"
                                :class="nutritionColorClass(dailyNutrition[day.day_number]?.protein ?? 0, dailyNutrition[day.day_number]?.targets?.protein ?? null)"
                            >
                                {{ dailyNutrition[day.day_number]?.protein ?? 0 }}g protein
                                <template v-if="dailyNutrition[day.day_number]?.targets?.protein">
                                    / {{ dailyNutrition[day.day_number].targets.protein }}g
                                </template>
                            </span>
                            <span
                                role="listitem"
                                :class="nutritionColorClass(dailyNutrition[day.day_number]?.carbs ?? 0, dailyNutrition[day.day_number]?.targets?.carbs ?? null)"
                            >
                                {{ dailyNutrition[day.day_number]?.carbs ?? 0 }}g carbs
                                <template v-if="dailyNutrition[day.day_number]?.targets?.carbs">
                                    / {{ dailyNutrition[day.day_number].targets.carbs }}g
                                </template>
                            </span>
                            <span
                                role="listitem"
                                :class="nutritionColorClass(dailyNutrition[day.day_number]?.fat ?? 0, dailyNutrition[day.day_number]?.targets?.fat ?? null)"
                            >
                                {{ dailyNutrition[day.day_number]?.fat ?? 0 }}g fat
                                <template v-if="dailyNutrition[day.day_number]?.targets?.fat">
                                    / {{ dailyNutrition[day.day_number].targets.fat }}g
                                </template>
                            </span>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Failed state -->
            <div
                v-if="plan.status === 'failed'"
                class="text-center py-16"
            >
                <div class="mx-auto h-16 w-16 rounded-full bg-red-100 dark:bg-red-900/20 flex items-center justify-center mb-4">
                    <svg class="h-8 w-8 text-red-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                </div>
                <h2 class="text-xl font-serif font-bold text-[#3A2520] dark:text-[#E8E0D4] mb-2">Generation failed</h2>
                <p class="text-[#6B5C55] dark:text-[#C9B8A6] mb-6">Something went wrong while generating your meal plan. Please try again.</p>
                <Link
                    href="/meal-plans/create"
                    class="inline-block rounded-full bg-[#C27B5B] hover:bg-[#A8664A] px-6 py-3 text-white font-semibold transition"
                >
                    Create New Plan
                </Link>
            </div>

            <!-- Grocery List -->
            <section
                v-if="plan.status === 'complete' && plan.grocery_list?.length > 0"
                class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl overflow-hidden print:border-black"
                aria-label="Grocery list"
            >
                <button
                    type="button"
                    class="w-full text-left px-6 py-4 flex items-center justify-between"
                    :class="groceryExpanded ? 'border-b border-[#EDE5DD] dark:border-[#3D3D39]' : ''"
                    @click="groceryExpanded = !groceryExpanded"
                    :aria-expanded="groceryExpanded"
                >
                    <div>
                        <h2 class="font-serif text-xl font-semibold text-[#3A2520] dark:text-[#F3EDE6]">
                            Grocery List
                            <span class="text-sm font-normal text-[#6B5C55] dark:text-[#C9B8A6] ml-2">{{ itemCount }} items</span>
                        </h2>
                        <div v-if="hasKrogerData" class="flex flex-wrap gap-x-4 gap-y-0.5 text-sm mt-1">
                            <span class="text-[#3A2520] dark:text-[#F3EDE6] font-semibold">
                                Checkout: ${{ checkoutTotal.toFixed(2) }}
                            </span>
                            <span class="text-[#7A8C5A] dark:text-emerald-400">
                                Recipe value: ${{ recipeCostTotal.toFixed(2) }}
                            </span>
                        </div>
                        <p v-else-if="recipeCostTotal > 0" class="text-sm text-[#6B5C55] dark:text-[#C9B8A6] mt-1">
                            {{ userZipCode ? 'Area estimate' : 'Estimated' }}: ${{ recipeCostTotal.toFixed(2) }}
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span
                            v-if="groceryExpanded"
                            @click.stop="printGroceryList"
                            class="px-3 py-1 rounded-lg text-xs font-medium border border-[#EDE5DD] dark:border-[#3D3D39] text-[#6B5C55] dark:text-[#C9B8A6] hover:border-[#C27B5B] hover:text-[#C27B5B] transition print:hidden"
                        >
                            Print
                        </span>
                        <svg
                            class="h-5 w-5 text-[#6B5C55] dark:text-[#C9B8A6] transition-transform duration-200"
                            :class="groceryExpanded ? 'rotate-180' : ''"
                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </button>

                <!-- Kroger price lookup (collapsible) -->
                <div v-show="groceryExpanded" class="px-6 py-4 border-b border-[#EDE5DD] dark:border-[#3D3D39] bg-[#FBF5F0]/50 dark:bg-[#2E2E2B]/50 print:hidden">
                    <p class="text-xs font-semibold uppercase tracking-wider text-[#6B5C55] dark:text-[#C9B8A6] mb-3">
                        {{ hasKrogerData ? 'Update prices from a store' : 'Get real prices from a nearby store' }}
                    </p>
                    <div class="flex flex-wrap gap-2 items-end">
                        <div>
                            <label class="text-xs text-[#6B5C55] dark:text-[#C9B8A6]">Zip Code</label>
                            <input
                                v-model="zipCode"
                                type="text"
                                maxlength="5"
                                placeholder="90210"
                                class="mt-1 w-28 rounded-lg bg-white dark:bg-[#2E2E2B] border border-[#EDE5DD] dark:border-[#3D3D39] px-3 py-2 text-sm text-[#3A2520] dark:text-[#F3EDE6] focus:outline-none focus:ring-2 focus:ring-[#C27B5B]"
                                @keydown.enter="findStores"
                            />
                        </div>
                        <button
                            @click="findStores"
                            :disabled="loadingStores || !/^\d{5}$/.test(zipCode.trim())"
                            class="px-4 py-2 rounded-lg text-sm font-medium bg-[#C27B5B] text-white hover:bg-[#A8664A] disabled:opacity-40 transition"
                        >
                            {{ loadingStores ? 'Finding...' : 'Find Stores' }}
                        </button>

                        <template v-if="stores.length > 0">
                            <select
                                v-model="selectedStore"
                                class="rounded-lg bg-white dark:bg-[#2E2E2B] border border-[#EDE5DD] dark:border-[#3D3D39] px-3 py-2 text-sm text-[#3A2520] dark:text-[#F3EDE6] flex-1 min-w-[180px]"
                            >
                                <option v-for="s in stores" :key="s.locationId" :value="s.locationId">
                                    {{ s.name }} — {{ s.address }}
                                </option>
                            </select>
                            <button
                                @click="getRealPrices"
                                :disabled="loadingPrices || !selectedStore"
                                class="px-5 py-2 rounded-lg text-sm font-semibold bg-[#7A8C5A] text-white hover:bg-[#6A7D4A] disabled:opacity-40 transition"
                            >
                                {{ loadingPrices ? 'Pricing...' : 'Get Prices' }}
                            </button>
                        </template>
                    </div>
                </div>

                <ul v-show="groceryExpanded" class="divide-y divide-[#EDE5DD] dark:divide-[#3D3D39]/50">
                    <li
                        v-for="(item, idx) in plan.grocery_list"
                        :key="idx"
                        class="flex items-center gap-3 px-6 py-3 cursor-pointer transition-colors hover:bg-[#FBF5F0] dark:hover:bg-[#2E2E2B]/50"
                        role="checkbox"
                        :aria-checked="checkedGroceryItems.has(idx)"
                        :aria-label="item.name"
                        tabindex="0"
                        @click="toggleGroceryItem(idx)"
                        @keydown.space.prevent="toggleGroceryItem(idx)"
                    >
                        <span
                            class="flex h-5 w-5 shrink-0 items-center justify-center rounded border-2 transition-colors print:border-black"
                            :class="checkedGroceryItems.has(idx)
                                ? 'border-[#7A8C5A] bg-[#7A8C5A] text-white'
                                : 'border-[#EDE5DD] dark:border-[#3D3D39]'"
                        >
                            <svg v-if="checkedGroceryItems.has(idx)" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </span>
                        <div
                            class="flex-1 min-w-0"
                            :class="checkedGroceryItems.has(idx) ? 'opacity-50' : ''"
                        >
                            <span class="text-sm text-[#3A2520] dark:text-[#F3EDE6]" :class="checkedGroceryItems.has(idx) && 'line-through'">
                                {{ item.name }}
                                <span v-if="item.quantity || item.unit" class="text-[#6B5C55] dark:text-[#C9B8A6]">
                                    &mdash; {{ item.quantity }}{{ item.unit ? ' ' + item.unit : '' }}
                                </span>
                            </span>
                            <p v-if="item.kroger_name && item.kroger_name !== item.name" class="text-[10px] text-[#6B5C55] dark:text-[#C9B8A6] truncate mt-0.5">
                                Kroger: {{ item.kroger_name }} {{ item.kroger_size ? `(${item.kroger_size})` : '' }}
                            </p>
                        </div>
                        <div class="shrink-0 text-right">
                            <template v-if="item.kroger_price != null">
                                <p class="text-sm font-semibold text-[#3A2520] dark:text-[#F3EDE6]">
                                    ${{ (item.kroger_price / 100).toFixed(2) }}
                                </p>
                                <p v-if="item.kroger_size" class="text-[10px] text-[#6B5C55] dark:text-[#C9B8A6]">
                                    {{ item.kroger_size }}
                                </p>
                                <p v-if="item.estimated_price_cents" class="text-[10px] text-[#7A8C5A] dark:text-emerald-400">
                                    recipe uses ~${{ (item.estimated_price_cents / 100).toFixed(2) }}
                                </p>
                            </template>
                            <template v-else-if="item.kroger_not_found && item.estimated_price_cents">
                                <p class="text-sm text-[#6B5C55] dark:text-[#C9B8A6]">
                                    ~${{ (item.estimated_price_cents / 100).toFixed(2) }}
                                </p>
                                <p class="text-[10px] text-amber-600 dark:text-amber-400">est. price</p>
                            </template>
                            <template v-else-if="item.estimated_price_cents">
                                <p class="text-xs text-[#6B5C55] dark:text-[#C9B8A6]">
                                    ~${{ (item.estimated_price_cents / 100).toFixed(2) }}
                                </p>
                            </template>
                        </div>
                    </li>
                </ul>
            </section>

            <!-- Delete meal plan -->
            <div class="pt-4 border-t border-[#EDE5DD] dark:border-[#3D3D39] mt-8">
                <button
                    type="button"
                    @click="showDeleteConfirm = true"
                    class="text-sm text-red-600 hover:text-red-700 hover:underline transition"
                >
                    Delete this meal plan
                </button>
            </div>

            <ConfirmDialog
                :open="showDeleteConfirm"
                title="Delete meal plan?"
                :message="`${plan.name} and its grocery list will be archived. You can ask support to restore it if needed.`"
                confirm-label="Delete"
                variant="danger"
                @confirm="deletePlan"
                @cancel="showDeleteConfirm = false"
            />
        </div>
    </MyLayout>
</template>

<style>
@media print {
    /* Hide everything except grocery list when printing */
    nav, footer, .print\\:hidden {
        display: none !important;
    }

    main {
        padding: 0 !important;
    }

    section[aria-label="Grocery list"] {
        border: 1px solid #000 !important;
        border-radius: 0 !important;
    }
}
</style>
