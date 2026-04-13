<script setup lang="ts">
import { useHead } from '@vueuse/head';
import MyLayout from '@/layouts/MyLayout.vue';
import RecipeItem from '@/shared/RecipeList/RecipeItem.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';

interface Recipe {
    id: number;
    name: string;
    slug: string;
    description: string;
    image_url: string;
    created_at: string;
    is_variation: boolean;
    calories_per_serving: number | null;
    ingredients: { name: string }[];
}

interface PaginatedRecipes {
    data: Recipe[];
    current_page: number;
    last_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

const props = defineProps<{
    recipes: PaginatedRecipes;
    tokenBalance: number;
    filters: {
        search: string;
        sort: string;
        variations: boolean;
        originals: boolean;
    };
}>();

useHead({ title: 'My Recipes | FridgeGPT' });

const search = ref(props.filters.search);
const sort = ref(props.filters.sort);
const showVariations = ref(props.filters.variations);
const showOriginals = ref(props.filters.originals);

let debounceTimer: ReturnType<typeof setTimeout>;

function applyFilters() {
    router.get('/recipes', {
        search: search.value || undefined,
        sort: sort.value !== 'newest' ? sort.value : undefined,
        variations: showVariations.value || undefined,
        originals: showOriginals.value || undefined,
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

watch([sort, showVariations, showOriginals], applyFilters);

function clearFilters() {
    search.value = '';
    sort.value = 'newest';
    showVariations.value = false;
    showOriginals.value = false;
    router.get('/recipes', {}, { preserveState: true, replace: true });
}

const hasActiveFilters = computed(() => {
    return search.value || sort.value !== 'newest' || showVariations.value || showOriginals.value;
});

const hasRecipes = computed(() => props.recipes.data.length > 0);

// Group recipes by date
const groupedRecipes = computed(() => {
    const groups: { label: string; recipes: Recipe[] }[] = [];
    const today = new Date().toDateString();
    const yesterday = new Date(Date.now() - 86400000).toDateString();

    let currentLabel = '';
    let currentGroup: Recipe[] = [];

    for (const recipe of props.recipes.data) {
        const date = new Date(recipe.created_at);
        const dateStr = date.toDateString();
        let label: string;

        if (dateStr === today) {
            label = 'Today';
        } else if (dateStr === yesterday) {
            label = 'Yesterday';
        } else {
            label = date.toLocaleDateString(undefined, { month: 'long', day: 'numeric', year: 'numeric' });
        }

        if (label !== currentLabel) {
            if (currentGroup.length > 0) {
                groups.push({ label: currentLabel, recipes: currentGroup });
            }
            currentLabel = label;
            currentGroup = [recipe];
        } else {
            currentGroup.push(recipe);
        }
    }

    if (currentGroup.length > 0) {
        groups.push({ label: currentLabel, recipes: currentGroup });
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
                    <h1 class="text-3xl font-bold font-serif text-[#3A2520] dark:text-[#E8E0D4]">My Recipes</h1>
                    <p class="text-sm text-[#6B5C55] dark:text-[#C9B8A6] mt-1">
                        {{ props.recipes.total }} recipe{{ props.recipes.total !== 1 ? 's' : '' }}
                    </p>
                </div>
                <Link
                    href="/create"
                    class="rounded-full bg-[#C27B5B] hover:bg-[#A8664A] px-5 py-2.5 text-sm font-semibold text-white transition focus:outline-none focus:ring-2 focus:ring-[#C27B5B]"
                    aria-label="Create a new recipe"
                >
                    + New Recipe
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
                        placeholder="Search by name or ingredient..."
                        class="w-full rounded-xl border border-[#EDE5DD] dark:border-[#3D3D39] bg-white dark:bg-[#222220] pl-10 pr-4 py-3 text-sm text-[#3A2520] dark:text-[#E8E0D4] placeholder-[#B0A196] dark:placeholder-[#6B5C55] focus:outline-none focus:ring-2 focus:ring-[#C27B5B] focus:border-transparent"
                        aria-label="Search recipes"
                    />
                </div>

                <!-- Filter row -->
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Sort -->
                    <select
                        v-model="sort"
                        class="rounded-lg border border-[#EDE5DD] dark:border-[#3D3D39] bg-white dark:bg-[#222220] px-3 py-2 text-sm text-[#3A2520] dark:text-[#E8E0D4] focus:outline-none focus:ring-2 focus:ring-[#C27B5B]"
                        aria-label="Sort recipes"
                    >
                        <option value="newest">Newest first</option>
                        <option value="oldest">Oldest first</option>
                        <option value="name">A to Z</option>
                    </select>

                    <!-- Filter chips -->
                    <button
                        @click="showOriginals = !showOriginals; showVariations = false"
                        class="rounded-full px-3 py-1.5 text-sm border transition"
                        :class="showOriginals
                            ? 'bg-[#C27B5B] border-[#C27B5B] text-white'
                            : 'border-[#EDE5DD] dark:border-[#3D3D39] text-[#6B5C55] dark:text-[#C9B8A6] hover:border-[#C27B5B]'"
                        aria-label="Show only original recipes"
                        :aria-pressed="showOriginals"
                    >
                        Originals
                    </button>
                    <button
                        @click="showVariations = !showVariations; showOriginals = false"
                        class="rounded-full px-3 py-1.5 text-sm border transition"
                        :class="showVariations
                            ? 'bg-[#C27B5B] border-[#C27B5B] text-white'
                            : 'border-[#EDE5DD] dark:border-[#3D3D39] text-[#6B5C55] dark:text-[#C9B8A6] hover:border-[#C27B5B]'"
                        aria-label="Show only variations"
                        :aria-pressed="showVariations"
                    >
                        Variations
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

            <!-- Recipe list grouped by date -->
            <div v-if="hasRecipes" class="space-y-8">
                <section v-for="group in groupedRecipes" :key="group.label">
                    <h2 class="text-sm font-semibold text-[#6B5C55] dark:text-[#C9B8A6] uppercase tracking-wider mb-3">{{ group.label }}</h2>
                    <div class="space-y-3">
                        <RecipeItem
                            v-for="recipe in group.recipes"
                            :key="recipe.id || recipe.slug"
                            :recipe="recipe"
                        />
                    </div>
                </section>
            </div>

            <!-- No results -->
            <div v-else-if="hasActiveFilters" class="text-center py-16">
                <p class="text-[#6B5C55] dark:text-[#C9B8A6] mb-4">No recipes match your search.</p>
                <button @click="clearFilters" class="text-[#C27B5B] hover:underline font-medium">Clear filters</button>
            </div>

            <!-- Empty state -->
            <div v-else class="text-center py-16">
                <div class="mx-auto h-16 w-16 rounded-full bg-[#FBF5F0] dark:bg-[#2E2E2B] flex items-center justify-center mb-4">
                    <svg class="h-8 w-8 text-[#C27B5B] dark:text-[#D4967E]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>
                    </svg>
                </div>
                <h2 class="text-xl font-serif font-bold text-[#3A2520] dark:text-[#E8E0D4] mb-2">No recipes yet</h2>
                <p class="text-[#6B5C55] dark:text-[#C9B8A6] mb-6">Generate your first recipe to get started.</p>
                <Link
                    href="/create"
                    class="inline-block rounded-full bg-[#C27B5B] hover:bg-[#A8664A] px-6 py-3 text-white font-semibold transition"
                >
                    Create a Recipe
                </Link>
            </div>

            <!-- Pagination -->
            <nav v-if="props.recipes.last_page > 1" class="flex justify-center gap-1 mt-10" aria-label="Recipe pagination">
                <template v-for="link in props.recipes.links" :key="link.label">
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
