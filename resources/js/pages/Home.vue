<script setup lang="ts">
import { useHead } from '@vueuse/head';
import MyLayout from '@/layouts/MyLayout.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Recipe {
    id: number;
    name: string;
    slug: string;
    calories_per_serving: number | null;
    created_at: string;
}

interface MealPlan {
    id: number;
    uuid: string;
    name: string;
    days: number;
    status: string;
    grocery_total_cents: number | null;
    tokens_spent: number;
    created_at: string;
}

interface Props {
    recipes: Recipe[] | null;
    mealPlans: MealPlan[] | null;
    activePlan: { uuid: string; name: string; status: string } | null;
    stats: { total_recipes: number; total_meal_plans: number; recipes_this_week: number } | null;
    tokenBalance: number;
    tokenCost: number;
    userStoreName: string | null;
}

const props = defineProps<Props>();
const page = usePage();
const user = computed(() => (page.props as any).auth?.user);
const isLoggedIn = computed(() => !!user.value);

useHead({ title: 'FridgeGPT — AI Recipe Generator' });

function formatDate(d: string): string {
    return new Date(d).toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
}

const statusLabels: Record<string, string> = {
    draft: 'Generating',
    generating: 'Generating',
    complete: 'Ready',
    failed: 'Failed',
};
</script>

<template>
    <MyLayout>
        <!-- Logged out — marketing hero -->
        <template v-if="!isLoggedIn">
            <section class="flex flex-col items-center justify-center min-h-[50vh] px-6 py-20 bg-[#FBF5F0] dark:bg-[#1A1A18]">
                <h1 class="text-5xl md:text-7xl font-bold font-serif tracking-tight text-[#3A2520] dark:text-[#F3EDE6] mb-4">FridgeGPT</h1>
                <p class="text-lg md:text-xl text-[#6B5C55] dark:text-[#C9B8A6] mb-2 text-center">AI-powered recipes from what's in your fridge</p>
                <p class="text-base text-[#6B5C55] dark:text-[#C9B8A6] mb-8 text-center max-w-md">Create recipes, plan meals, build grocery lists with real store prices.</p>
                <div class="flex gap-3">
                    <Link href="/signup" class="rounded-full bg-[#C27B5B] hover:bg-[#A8664A] px-8 py-3.5 text-base font-semibold text-white transition">
                        Get Started Free
                    </Link>
                    <Link href="/login" class="rounded-full border border-[#EDE5DD] dark:border-[#3D3D39] px-8 py-3.5 text-base font-semibold text-[#3A2520] dark:text-[#F3EDE6] hover:bg-white dark:hover:bg-[#222220] transition">
                        Sign In
                    </Link>
                </div>
            </section>
        </template>

        <!-- Logged in — dashboard -->
        <template v-else>
            <div class="max-w-5xl mx-auto px-4 py-8 md:py-12">
                <!-- Greeting + quick stats -->
                <div class="mb-8">
                    <h1 class="font-serif text-3xl md:text-4xl font-bold text-[#3A2520] dark:text-[#F3EDE6]">
                        Hey, {{ user.name.split(' ')[0] }}
                    </h1>
                    <p class="text-[#6B5C55] dark:text-[#C9B8A6] mt-1">
                        {{ stats?.total_recipes ?? 0 }} recipes · {{ stats?.total_meal_plans ?? 0 }} meal plans · {{ tokenBalance }} tokens
                    </p>
                </div>

                <!-- Active generating plan banner -->
                <Link
                    v-if="activePlan"
                    :href="`/meal-plans/${activePlan.uuid}`"
                    class="flex items-center gap-4 p-4 mb-6 rounded-2xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 hover:border-amber-300 transition"
                >
                    <svg class="animate-spin h-5 w-5 text-amber-600 shrink-0" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                    </svg>
                    <div>
                        <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">{{ activePlan.name }} is generating...</p>
                        <p class="text-xs text-amber-600 dark:text-amber-400">Tap to view progress</p>
                    </div>
                </Link>

                <!-- Quick actions -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
                    <Link
                        href="/create"
                        class="group flex items-center gap-4 p-5 rounded-2xl bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] hover:border-[#C27B5B] hover:shadow-sm transition"
                    >
                        <div class="h-12 w-12 rounded-xl bg-[#C27B5B]/10 flex items-center justify-center group-hover:bg-[#C27B5B]/20 transition">
                            <svg class="h-6 w-6 text-[#C27B5B]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold text-[#3A2520] dark:text-[#F3EDE6]">Create Recipe</p>
                            <p class="text-xs text-[#6B5C55] dark:text-[#C9B8A6]">From ingredients you have · 1 token</p>
                        </div>
                    </Link>

                    <Link
                        href="/meal-plans/create"
                        class="group flex items-center gap-4 p-5 rounded-2xl bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] hover:border-[#C27B5B] hover:shadow-sm transition"
                    >
                        <div class="h-12 w-12 rounded-xl bg-[#7A8C5A]/10 flex items-center justify-center group-hover:bg-[#7A8C5A]/20 transition">
                            <svg class="h-6 w-6 text-[#7A8C5A]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold text-[#3A2520] dark:text-[#F3EDE6]">Plan Meals</p>
                            <p class="text-xs text-[#6B5C55] dark:text-[#C9B8A6]">
                                Multi-day plans with grocery list
                                <template v-if="userStoreName"> · {{ userStoreName }}</template>
                            </p>
                        </div>
                    </Link>
                </div>

                <!-- Two-column layout -->
                <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
                    <!-- Recent recipes (3 cols) -->
                    <div class="lg:col-span-3">
                        <div class="flex items-center justify-between mb-3">
                            <h2 class="font-serif text-xl font-semibold text-[#3A2520] dark:text-[#F3EDE6]">Recent Recipes</h2>
                            <Link href="/recipes" class="text-xs font-medium text-[#C27B5B] hover:underline">View all &rarr;</Link>
                        </div>

                        <div v-if="recipes && recipes.length > 0" class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl overflow-hidden">
                            <Link
                                v-for="r in recipes"
                                :key="r.id"
                                :href="`/recipe/${r.slug}`"
                                class="flex items-center justify-between px-5 py-3.5 hover:bg-[#FBF5F0] dark:hover:bg-[#2E2E2B] transition border-b border-[#EDE5DD] dark:border-[#3D3D39] last:border-b-0"
                            >
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="h-9 w-9 rounded-lg bg-[#FBF5F0] dark:bg-[#2E2E2B] flex items-center justify-center shrink-0">
                                        <span class="text-sm font-bold font-serif text-[#C27B5B]">{{ r.name.charAt(0) }}</span>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-[#3A2520] dark:text-[#F3EDE6] truncate">{{ r.name }}</p>
                                        <p class="text-[10px] text-[#6B5C55] dark:text-[#C9B8A6]">
                                            {{ formatDate(r.created_at) }}
                                            <template v-if="r.calories_per_serving"> · {{ Math.round(r.calories_per_serving) }} cal</template>
                                        </p>
                                    </div>
                                </div>
                                <svg class="h-4 w-4 text-[#C9B8A6] shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </Link>
                        </div>

                        <div v-else class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl p-8 text-center">
                            <p class="text-sm text-[#6B5C55] dark:text-[#C9B8A6] mb-3">No recipes yet</p>
                            <Link href="/create" class="text-sm font-semibold text-[#C27B5B] hover:underline">Create your first recipe &rarr;</Link>
                        </div>
                    </div>

                    <!-- Sidebar (2 cols) -->
                    <div class="lg:col-span-2 space-y-4">
                        <!-- Tokens -->
                        <div class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl p-5">
                            <p class="text-xs uppercase tracking-wider text-[#6B5C55] dark:text-[#C9B8A6] font-semibold">Token Balance</p>
                            <p class="font-serif text-3xl font-bold text-[#3A2520] dark:text-[#F3EDE6] mt-1">{{ tokenBalance }}</p>
                            <Link
                                href="/tokens"
                                class="mt-3 inline-block w-full text-center rounded-xl bg-[#C27B5B] hover:bg-[#A8664A] px-4 py-2.5 text-sm font-semibold text-white transition"
                            >
                                Buy Tokens
                            </Link>
                        </div>

                        <!-- Recent meal plans -->
                        <div class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl overflow-hidden">
                            <div class="px-5 py-3.5 border-b border-[#EDE5DD] dark:border-[#3D3D39] flex items-center justify-between">
                                <h3 class="font-serif text-sm font-semibold text-[#3A2520] dark:text-[#F3EDE6]">Meal Plans</h3>
                                <Link href="/meal-plans" class="text-[10px] font-medium text-[#C27B5B] hover:underline">All &rarr;</Link>
                            </div>
                            <template v-if="mealPlans && mealPlans.length > 0">
                                <Link
                                    v-for="p in mealPlans"
                                    :key="p.id"
                                    :href="`/meal-plans/${p.uuid}`"
                                    class="block px-5 py-3 hover:bg-[#FBF5F0] dark:hover:bg-[#2E2E2B] transition border-b border-[#EDE5DD] dark:border-[#3D3D39] last:border-b-0"
                                >
                                    <p class="text-sm font-medium text-[#3A2520] dark:text-[#F3EDE6] truncate">{{ p.name }}</p>
                                    <p class="text-[10px] text-[#6B5C55] dark:text-[#C9B8A6] mt-0.5">
                                        {{ p.days }}d · {{ statusLabels[p.status] || p.status }}
                                        <template v-if="p.grocery_total_cents"> · ${{ (p.grocery_total_cents / 100).toFixed(0) }}</template>
                                    </p>
                                </Link>
                            </template>
                            <div v-else class="px-5 py-6 text-center">
                                <p class="text-xs text-[#6B5C55] dark:text-[#C9B8A6]">No meal plans yet</p>
                            </div>
                        </div>

                        <!-- Low tokens warning -->
                        <Link
                            v-if="tokenBalance < 5"
                            href="/tokens"
                            class="flex items-center gap-3 p-4 rounded-2xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 hover:border-amber-300 transition"
                        >
                            <svg class="h-5 w-5 text-amber-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                            <div>
                                <p class="text-xs font-semibold text-amber-800 dark:text-amber-300">Running low on tokens</p>
                                <p class="text-[10px] text-amber-600 dark:text-amber-400">{{ tokenBalance }} remaining — tap to buy more</p>
                            </div>
                        </Link>
                    </div>
                </div>
            </div>
        </template>
    </MyLayout>
</template>
