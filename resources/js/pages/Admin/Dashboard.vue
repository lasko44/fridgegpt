<script setup lang="ts">
import { useHead } from '@vueuse/head';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    users: {
        total: number;
        admins: number;
        verified: number;
        new_today: number;
        new_this_week: number;
        new_this_month: number;
        active_7d: number;
        active_30d: number;
    };
    tokens: {
        total_balance: number;
        purchased_today: number;
        purchased_this_week: number;
        purchased_this_month: number;
        spent_today: number;
        spent_this_week: number;
        spent_this_month: number;
        revenue_today_cents: number;
        revenue_this_week_cents: number;
        revenue_this_month_cents: number;
    };
    content: {
        total_recipes: number;
        recipes_today: number;
        recipes_this_week: number;
        total_meal_plans: number;
        meal_plans_this_week: number;
        meal_plans_failed: number;
    };
    system: {
        pending_jobs: number;
        failed_jobs: number;
    };
    charts: {
        signups: { date: string; value: number }[];
        recipes: { date: string; value: number }[];
        revenue: { date: string; value: number }[];
    };
    topUsers: {
        uuid: string;
        name: string;
        username: string;
        email: string;
        token_balance: number;
        recipes_count: number;
    }[];
}

const props = defineProps<Props>();

useHead({ title: 'Admin Dashboard | FridgeGPT' });

function dollars(cents: number): string {
    return '$' + (cents / 100).toFixed(2);
}

function maxValue(series: { value: number }[]): number {
    return Math.max(1, ...series.map((s) => s.value));
}

const signupsMax = computed(() => maxValue(props.charts.signups));
const recipesMax = computed(() => maxValue(props.charts.recipes));
const revenueMax = computed(() => maxValue(props.charts.revenue));
</script>

<template>
    <AdminLayout>
        <header class="mb-8">
            <h1 class="font-serif text-3xl font-bold text-[#3A2520] dark:text-[#F3EDE6]">Dashboard</h1>
            <p class="text-sm text-[#6B5C55] dark:text-[#C9B8A6] mt-1">Platform overview</p>
        </header>

        <!-- Stat cards grid -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <!-- Users -->
            <div class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl p-5">
                <p class="text-xs uppercase tracking-wider text-[#6B5C55] dark:text-[#C9B8A6] font-semibold">Total Users</p>
                <p class="font-serif text-3xl font-bold mt-1 text-[#3A2520] dark:text-[#F3EDE6]">{{ users.total.toLocaleString() }}</p>
                <p class="text-xs text-[#7A8C5A] mt-2">+{{ users.new_this_week }} this week</p>
            </div>

            <div class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl p-5">
                <p class="text-xs uppercase tracking-wider text-[#6B5C55] dark:text-[#C9B8A6] font-semibold">Active (7d)</p>
                <p class="font-serif text-3xl font-bold mt-1 text-[#3A2520] dark:text-[#F3EDE6]">{{ users.active_7d.toLocaleString() }}</p>
                <p class="text-xs text-[#6B5C55] dark:text-[#C9B8A6] mt-2">{{ users.active_30d }} in 30d</p>
            </div>

            <div class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl p-5">
                <p class="text-xs uppercase tracking-wider text-[#6B5C55] dark:text-[#C9B8A6] font-semibold">Revenue (7d)</p>
                <p class="font-serif text-3xl font-bold mt-1 text-[#3A2520] dark:text-[#F3EDE6]">{{ dollars(tokens.revenue_this_week_cents) }}</p>
                <p class="text-xs text-[#7A8C5A] mt-2">{{ dollars(tokens.revenue_today_cents) }} today</p>
            </div>

            <div class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl p-5">
                <p class="text-xs uppercase tracking-wider text-[#6B5C55] dark:text-[#C9B8A6] font-semibold">Tokens Spent (7d)</p>
                <p class="font-serif text-3xl font-bold mt-1 text-[#3A2520] dark:text-[#F3EDE6]">{{ tokens.spent_this_week.toLocaleString() }}</p>
                <p class="text-xs text-[#6B5C55] dark:text-[#C9B8A6] mt-2">{{ tokens.spent_today.toLocaleString() }} today</p>
            </div>

            <div class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl p-5">
                <p class="text-xs uppercase tracking-wider text-[#6B5C55] dark:text-[#C9B8A6] font-semibold">Recipes</p>
                <p class="font-serif text-3xl font-bold mt-1 text-[#3A2520] dark:text-[#F3EDE6]">{{ content.total_recipes.toLocaleString() }}</p>
                <p class="text-xs text-[#7A8C5A] mt-2">+{{ content.recipes_this_week }} this week</p>
            </div>

            <div class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl p-5">
                <p class="text-xs uppercase tracking-wider text-[#6B5C55] dark:text-[#C9B8A6] font-semibold">Meal Plans</p>
                <p class="font-serif text-3xl font-bold mt-1 text-[#3A2520] dark:text-[#F3EDE6]">{{ content.total_meal_plans.toLocaleString() }}</p>
                <p class="text-xs text-[#6B5C55] dark:text-[#C9B8A6] mt-2">+{{ content.meal_plans_this_week }} this week</p>
            </div>

            <div class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl p-5">
                <p class="text-xs uppercase tracking-wider text-[#6B5C55] dark:text-[#C9B8A6] font-semibold">Pending Jobs</p>
                <p class="font-serif text-3xl font-bold mt-1 text-[#3A2520] dark:text-[#F3EDE6]">{{ system.pending_jobs }}</p>
                <p class="text-xs mt-2" :class="system.failed_jobs > 0 ? 'text-red-600' : 'text-[#6B5C55] dark:text-[#C9B8A6]'">
                    {{ system.failed_jobs }} failed
                </p>
            </div>

            <div class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl p-5">
                <p class="text-xs uppercase tracking-wider text-[#6B5C55] dark:text-[#C9B8A6] font-semibold">Token Balance</p>
                <p class="font-serif text-3xl font-bold mt-1 text-[#3A2520] dark:text-[#F3EDE6]">{{ tokens.total_balance.toLocaleString() }}</p>
                <p class="text-xs text-[#6B5C55] dark:text-[#C9B8A6] mt-2">across all users</p>
            </div>
        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-8">
            <div class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl p-5">
                <h3 class="font-serif text-base font-semibold mb-4 text-[#3A2520] dark:text-[#F3EDE6]">Signups (14d)</h3>
                <div class="flex items-end gap-1.5 h-32">
                    <div
                        v-for="(point, idx) in charts.signups"
                        :key="idx"
                        class="flex-1 bg-[#C27B5B] rounded-t hover:bg-[#A8664A] transition relative group"
                        :style="{ height: `${(point.value / signupsMax) * 100}%`, minHeight: '2px' }"
                    >
                        <span class="absolute -top-6 left-1/2 -translate-x-1/2 text-xs bg-[#3A2520] text-white px-1.5 py-0.5 rounded opacity-0 group-hover:opacity-100 transition whitespace-nowrap">
                            {{ point.value }}
                        </span>
                    </div>
                </div>
                <div class="flex justify-between text-[10px] text-[#6B5C55] dark:text-[#C9B8A6] mt-2">
                    <span>{{ charts.signups[0]?.date }}</span>
                    <span>{{ charts.signups[charts.signups.length - 1]?.date }}</span>
                </div>
            </div>

            <div class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl p-5">
                <h3 class="font-serif text-base font-semibold mb-4 text-[#3A2520] dark:text-[#F3EDE6]">Recipes (14d)</h3>
                <div class="flex items-end gap-1.5 h-32">
                    <div
                        v-for="(point, idx) in charts.recipes"
                        :key="idx"
                        class="flex-1 bg-[#7A8C5A] rounded-t transition relative group"
                        :style="{ height: `${(point.value / recipesMax) * 100}%`, minHeight: '2px' }"
                    >
                        <span class="absolute -top-6 left-1/2 -translate-x-1/2 text-xs bg-[#3A2520] text-white px-1.5 py-0.5 rounded opacity-0 group-hover:opacity-100 transition whitespace-nowrap">
                            {{ point.value }}
                        </span>
                    </div>
                </div>
                <div class="flex justify-between text-[10px] text-[#6B5C55] dark:text-[#C9B8A6] mt-2">
                    <span>{{ charts.recipes[0]?.date }}</span>
                    <span>{{ charts.recipes[charts.recipes.length - 1]?.date }}</span>
                </div>
            </div>

            <div class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl p-5">
                <h3 class="font-serif text-base font-semibold mb-4 text-[#3A2520] dark:text-[#F3EDE6]">Revenue (14d)</h3>
                <div class="flex items-end gap-1.5 h-32">
                    <div
                        v-for="(point, idx) in charts.revenue"
                        :key="idx"
                        class="flex-1 bg-[#D4967E] rounded-t transition relative group"
                        :style="{ height: `${(point.value / revenueMax) * 100}%`, minHeight: '2px' }"
                    >
                        <span class="absolute -top-6 left-1/2 -translate-x-1/2 text-xs bg-[#3A2520] text-white px-1.5 py-0.5 rounded opacity-0 group-hover:opacity-100 transition whitespace-nowrap">
                            ${{ point.value.toFixed(0) }}
                        </span>
                    </div>
                </div>
                <div class="flex justify-between text-[10px] text-[#6B5C55] dark:text-[#C9B8A6] mt-2">
                    <span>{{ charts.revenue[0]?.date }}</span>
                    <span>{{ charts.revenue[charts.revenue.length - 1]?.date }}</span>
                </div>
            </div>
        </div>

        <!-- Top Users -->
        <div class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl overflow-hidden">
            <div class="px-5 py-4 border-b border-[#EDE5DD] dark:border-[#3D3D39] flex items-center justify-between">
                <h2 class="font-serif text-lg font-semibold text-[#3A2520] dark:text-[#F3EDE6]">Top Users by Recipes</h2>
                <Link href="/admin/users" class="text-xs text-[#C27B5B] hover:underline">View all &rarr;</Link>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-[#FBF5F0] dark:bg-[#2E2E2B] text-[#6B5C55] dark:text-[#C9B8A6] text-xs uppercase">
                    <tr>
                        <th class="text-left px-5 py-3">User</th>
                        <th class="text-left px-5 py-3 hidden md:table-cell">Email</th>
                        <th class="text-right px-5 py-3">Tokens</th>
                        <th class="text-right px-5 py-3">Recipes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#EDE5DD] dark:divide-[#3D3D39]">
                    <tr v-for="user in topUsers" :key="user.uuid" class="hover:bg-[#FBF5F0] dark:hover:bg-[#2E2E2B] transition">
                        <td class="px-5 py-3">
                            <Link :href="`/admin/users/${user.uuid}`" class="font-medium text-[#3A2520] dark:text-[#F3EDE6] hover:text-[#C27B5B]">
                                {{ user.name }}
                            </Link>
                        </td>
                        <td class="px-5 py-3 text-[#6B5C55] dark:text-[#C9B8A6] hidden md:table-cell">{{ user.email }}</td>
                        <td class="px-5 py-3 text-right font-medium text-[#3A2520] dark:text-[#F3EDE6]">{{ user.token_balance }}</td>
                        <td class="px-5 py-3 text-right text-[#3A2520] dark:text-[#F3EDE6]">{{ user.recipes_count }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>
