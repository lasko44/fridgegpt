<script setup lang="ts">
import { useHead } from '@vueuse/head';
import MyLayout from '@/layouts/MyLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';

interface MealPlan {
    uuid: string;
    name: string;
    days: number;
    meals_per_day: string[];
    status: 'draft' | 'generating' | 'complete' | 'failed';
    created_at: string;
    tokens_spent: number;
    budget_cents: number | null;
    grocery_total_cents: number | null;
}

interface PaginatedMealPlans {
    data: MealPlan[];
    current_page: number;
    last_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

const props = defineProps<{
    plans: PaginatedMealPlans;
    tokenBalance: number;
    filters: {
        search: string;
        sort: string;
        status: string;
    };
}>();

useHead({ title: 'Meal Plans | FridgeGPT' });

const search = ref(props.filters.search);
const sort = ref(props.filters.sort);
const statusFilter = ref(props.filters.status);

let debounceTimer: ReturnType<typeof setTimeout>;

function applyFilters() {
    router.get('/meal-plans', {
        search: search.value || undefined,
        sort: sort.value !== 'newest' ? sort.value : undefined,
        status: statusFilter.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

watch(search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(applyFilters, 400);
});

watch([sort, statusFilter], applyFilters);

function clearFilters() {
    search.value = '';
    sort.value = 'newest';
    statusFilter.value = '';
    router.get('/meal-plans', {}, { preserveState: true, replace: true });
}

const hasActiveFilters = computed(() => {
    return search.value || sort.value !== 'newest' || statusFilter.value;
});

const hasPlans = computed(() => props.plans.data.length > 0);

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

const statusFilterOptions = [
    { value: '', label: 'All' },
    { value: 'generating', label: 'Generating' },
    { value: 'complete', label: 'Ready' },
    { value: 'failed', label: 'Failed' },
];

function formatDate(dateStr: string): string {
    const date = new Date(dateStr);
    const now = new Date();
    const today = now.toDateString();
    const yesterday = new Date(Date.now() - 86400000).toDateString();
    const dateString = date.toDateString();

    if (dateString === today) return 'Today';
    if (dateString === yesterday) return 'Yesterday';
    return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
}

// Group plans by date
const groupedPlans = computed(() => {
    const groups: { label: string; plans: MealPlan[] }[] = [];
    let currentLabel = '';
    let currentGroup: MealPlan[] = [];

    for (const plan of props.plans.data) {
        const label = formatDate(plan.created_at);
        if (label !== currentLabel) {
            if (currentGroup.length > 0) {
                groups.push({ label: currentLabel, plans: currentGroup });
            }
            currentLabel = label;
            currentGroup = [plan];
        } else {
            currentGroup.push(plan);
        }
    }

    if (currentGroup.length > 0) {
        groups.push({ label: currentLabel, plans: currentGroup });
    }

    return groups;
});
</script>

<template>
    <MyLayout>
        <div class="mx-auto max-w-3xl px-4 py-10">
            <!-- Header -->
            <div class="flex items-start justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-bold font-serif text-[#3A2520] dark:text-[#E8E0D4]">Meal Plans</h1>
                    <p class="text-sm text-[#6B5C55] dark:text-[#C9B8A6] mt-1">
                        {{ props.plans.total }} plan{{ props.plans.total !== 1 ? 's' : '' }}
                    </p>
                </div>
                <Link
                    href="/meal-plans/create"
                    class="rounded-full bg-[#C27B5B] hover:bg-[#A8664A] px-5 py-2.5 text-sm font-semibold text-white transition focus:outline-none focus:ring-2 focus:ring-[#C27B5B]"
                    aria-label="Create a new meal plan"
                >
                    + New Plan
                </Link>
            </div>

            <!-- Search + Filters -->
            <div class="space-y-3 mb-8">
                <!-- Search bar -->
                <div class="relative">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-[#6B5C55] dark:text-[#C9B8A6]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                    </svg>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search meal plans..."
                        class="w-full rounded-xl border border-[#EDE5DD] dark:border-[#3D3D39] bg-white dark:bg-[#222220] pl-10 pr-4 py-3 text-sm text-[#3A2520] dark:text-[#E8E0D4] placeholder-[#B0A196] dark:placeholder-[#6B5C55] focus:outline-none focus:ring-2 focus:ring-[#C27B5B] focus:border-transparent"
                        aria-label="Search meal plans"
                    />
                </div>

                <!-- Filter row -->
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Sort -->
                    <select
                        v-model="sort"
                        class="rounded-lg border border-[#EDE5DD] dark:border-[#3D3D39] bg-white dark:bg-[#222220] px-3 py-2 text-sm text-[#3A2520] dark:text-[#E8E0D4] focus:outline-none focus:ring-2 focus:ring-[#C27B5B]"
                        aria-label="Sort meal plans"
                    >
                        <option value="newest">Newest first</option>
                        <option value="oldest">Oldest first</option>
                        <option value="name">A to Z</option>
                        <option value="budget">Highest cost</option>
                    </select>

                    <!-- Status filter chips -->
                    <button
                        v-for="option in statusFilterOptions"
                        :key="option.value"
                        @click="statusFilter = statusFilter === option.value ? '' : option.value"
                        class="rounded-full px-3 py-1.5 text-sm border transition"
                        :class="statusFilter === option.value
                            ? 'bg-[#C27B5B] border-[#C27B5B] text-white'
                            : 'border-[#EDE5DD] dark:border-[#3D3D39] text-[#6B5C55] dark:text-[#C9B8A6] hover:border-[#C27B5B]'"
                        :aria-label="'Filter by status: ' + option.label"
                        :aria-pressed="statusFilter === option.value"
                    >
                        {{ option.label }}
                    </button>

                    <!-- Clear filters -->
                    <button
                        v-if="hasActiveFilters"
                        @click="clearFilters"
                        class="text-xs text-[#C27B5B] hover:underline focus:outline-none"
                        aria-label="Clear all filters"
                    >
                        Clear filters
                    </button>
                </div>
            </div>

            <!-- Plan list grouped by date -->
            <div v-if="hasPlans" class="space-y-8">
                <section v-for="group in groupedPlans" :key="group.label">
                    <h2 class="text-sm font-semibold text-[#6B5C55] dark:text-[#C9B8A6] uppercase tracking-wider mb-3">{{ group.label }}</h2>
                    <div class="space-y-3">
                        <Link
                            v-for="plan in group.plans"
                            :key="plan.uuid"
                            :href="`/meal-plans/${plan.uuid}`"
                            class="block rounded-2xl bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] p-5 hover:border-[#C27B5B] hover:shadow-sm transition focus:outline-none focus:ring-2 focus:ring-[#C27B5B]"
                            :aria-label="'View meal plan: ' + plan.name"
                        >
                            <div class="flex items-center gap-4">
                                <!-- Icon -->
                                <div class="h-12 w-12 rounded-xl bg-[#FBF5F0] dark:bg-[#2E2E2B] flex items-center justify-center flex-shrink-0" aria-hidden="true">
                                    <svg class="h-6 w-6 text-[#7A8C5A]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                    </svg>
                                </div>

                                <!-- Content -->
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-medium text-[#3A2520] dark:text-[#F3EDE6] truncate">{{ plan.name }}</h3>
                                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mt-1">
                                        <span class="text-xs text-[#6B5C55] dark:text-[#C9B8A6]">{{ plan.days }}d &middot; {{ plan.meals_per_day.length }} meals/day</span>
                                        <span
                                            class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium"
                                            :class="statusColors[plan.status] || statusColors.draft"
                                        >
                                            {{ statusLabels[plan.status] || plan.status }}
                                        </span>
                                        <span v-if="plan.grocery_total_cents" class="text-xs text-[#6B5C55] dark:text-[#C9B8A6]">
                                            &middot; ~${{ (plan.grocery_total_cents / 100).toFixed(0) }}
                                        </span>
                                        <span class="text-xs text-[#6B5C55] dark:text-[#C9B8A6]">
                                            &middot; {{ plan.tokens_spent }} tokens
                                        </span>
                                    </div>
                                </div>

                                <!-- Chevron -->
                                <svg class="h-4 w-4 text-[#C9B8A6] dark:text-[#6B5C55] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </div>
                        </Link>
                    </div>
                </section>
            </div>

            <!-- No results (with filters) -->
            <div v-else-if="hasActiveFilters" class="text-center py-16">
                <p class="text-[#6B5C55] dark:text-[#C9B8A6] mb-4">No meal plans match your filters.</p>
                <button @click="clearFilters" class="text-[#C27B5B] hover:underline font-medium">Clear filters</button>
            </div>

            <!-- Empty state -->
            <div v-else class="text-center py-16">
                <div class="mx-auto h-16 w-16 rounded-full bg-[#FBF5F0] dark:bg-[#2E2E2B] flex items-center justify-center mb-4">
                    <svg class="h-8 w-8 text-[#C27B5B] dark:text-[#D4967E]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                    </svg>
                </div>
                <h2 class="text-xl font-serif font-bold text-[#3A2520] dark:text-[#E8E0D4] mb-2">No meal plans yet</h2>
                <p class="text-[#6B5C55] dark:text-[#C9B8A6] mb-6">Create your first one.</p>
                <Link
                    href="/meal-plans/create"
                    class="inline-block rounded-full bg-[#C27B5B] hover:bg-[#A8664A] px-6 py-3 text-white font-semibold transition"
                >
                    Create a Meal Plan
                </Link>
            </div>

            <!-- Pagination -->
            <nav v-if="props.plans.last_page > 1" class="flex justify-center gap-1 mt-10" aria-label="Meal plan pagination">
                <template v-for="link in props.plans.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="px-3 py-1.5 text-sm rounded-lg transition"
                        :class="link.active
                            ? 'bg-[#C27B5B] text-white'
                            : 'text-[#6B5C55] dark:text-[#C9B8A6] hover:bg-[#FBF5F0] dark:hover:bg-[#2E2E2B]'"
                        v-html="link.label"
                        :aria-current="link.active ? 'page' : undefined"
                        preserve-scroll
                    />
                    <span
                        v-else
                        class="px-3 py-1.5 text-sm text-[#C9B8A6] dark:text-[#6B5C55]"
                        v-html="link.label"
                    />
                </template>
            </nav>
        </div>
    </MyLayout>
</template>
