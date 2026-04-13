<script setup lang="ts">
import { useHead } from '@vueuse/head';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

function debounce<T extends (...args: any[]) => void>(fn: T, ms: number): T {
    let timer: ReturnType<typeof setTimeout> | null = null;
    return ((...args: any[]) => {
        if (timer) clearTimeout(timer);
        timer = setTimeout(() => fn(...args), ms);
    }) as T;
}

interface User {
    uuid: string;
    name: string;
    username: string;
    email: string;
    token_balance: number;
    is_admin: boolean;
    email_verified_at: string | null;
    created_at: string;
}

interface Props {
    users: {
        data: User[];
        current_page: number;
        last_page: number;
        total: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: {
        search: string | null;
        sort: string;
        admins_only: boolean;
    };
}

const props = defineProps<Props>();

useHead({ title: 'Users | Admin' });

const search = ref(props.filters.search ?? '');
const sort = ref(props.filters.sort);
const adminsOnly = ref(props.filters.admins_only);

const update = debounce(() => {
    router.get('/admin/users', {
        search: search.value || undefined,
        sort: sort.value !== 'newest' ? sort.value : undefined,
        admins_only: adminsOnly.value || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true });
}, 300);

watch([search, sort, adminsOnly], update);

function formatDate(d: string): string {
    return new Date(d).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}
</script>

<template>
    <AdminLayout>
        <header class="mb-6">
            <h1 class="font-serif text-3xl font-bold text-[#3A2520] dark:text-[#F3EDE6]">Users</h1>
            <p class="text-sm text-[#6B5C55] dark:text-[#C9B8A6] mt-1">{{ users.total.toLocaleString() }} total</p>
        </header>

        <!-- Filters -->
        <div class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-xl p-4 mb-4 flex flex-wrap gap-3">
            <input
                v-model="search"
                type="search"
                placeholder="Search by name, email, username..."
                class="flex-1 min-w-[200px] rounded-lg bg-[#FBF5F0] dark:bg-[#2E2E2B] border border-[#EDE5DD] dark:border-[#3D3D39] px-3 py-2 text-sm text-[#3A2520] dark:text-[#F3EDE6] focus:outline-none focus:ring-2 focus:ring-[#C27B5B]"
            />
            <select
                v-model="sort"
                class="rounded-lg bg-[#FBF5F0] dark:bg-[#2E2E2B] border border-[#EDE5DD] dark:border-[#3D3D39] px-3 py-2 text-sm text-[#3A2520] dark:text-[#F3EDE6]"
            >
                <option value="newest">Newest first</option>
                <option value="oldest">Oldest first</option>
                <option value="name">Name A-Z</option>
                <option value="tokens">Most tokens</option>
            </select>
            <label class="flex items-center gap-2 text-sm text-[#6B5C55] dark:text-[#C9B8A6]">
                <input v-model="adminsOnly" type="checkbox" class="rounded" />
                Admins only
            </label>
        </div>

        <!-- Table -->
        <div class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-[#FBF5F0] dark:bg-[#2E2E2B] text-[#6B5C55] dark:text-[#C9B8A6] text-xs uppercase">
                    <tr>
                        <th class="text-left px-5 py-3">User</th>
                        <th class="text-left px-5 py-3 hidden lg:table-cell">Email</th>
                        <th class="text-left px-5 py-3 hidden md:table-cell">Joined</th>
                        <th class="text-right px-5 py-3">Tokens</th>
                        <th class="text-right px-5 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#EDE5DD] dark:divide-[#3D3D39]">
                    <tr v-for="u in users.data" :key="u.uuid" class="hover:bg-[#FBF5F0] dark:hover:bg-[#2E2E2B]">
                        <td class="px-5 py-3">
                            <Link :href="`/admin/users/${u.uuid}`" class="font-medium text-[#3A2520] dark:text-[#F3EDE6] hover:text-[#C27B5B]">
                                {{ u.name }}
                            </Link>
                            <p class="text-xs text-[#6B5C55] dark:text-[#C9B8A6]">@{{ u.username }}</p>
                        </td>
                        <td class="px-5 py-3 text-[#6B5C55] dark:text-[#C9B8A6] hidden lg:table-cell">{{ u.email }}</td>
                        <td class="px-5 py-3 text-[#6B5C55] dark:text-[#C9B8A6] hidden md:table-cell">{{ formatDate(u.created_at) }}</td>
                        <td class="px-5 py-3 text-right font-medium text-[#3A2520] dark:text-[#F3EDE6]">{{ u.token_balance }}</td>
                        <td class="px-5 py-3 text-right">
                            <span v-if="u.is_admin" class="inline-block px-2 py-0.5 rounded-full text-xs font-medium bg-[#C27B5B]/10 text-[#C27B5B]">admin</span>
                            <span v-else-if="!u.email_verified_at" class="inline-block px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">unverified</span>
                            <span v-else class="inline-block px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">active</span>
                        </td>
                    </tr>
                    <tr v-if="users.data.length === 0">
                        <td colspan="5" class="px-5 py-12 text-center text-[#6B5C55] dark:text-[#C9B8A6]">No users found</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="users.last_page > 1" class="mt-4 flex flex-wrap gap-1.5 justify-center">
            <Link
                v-for="link in users.links"
                :key="link.label"
                :href="link.url || '#'"
                v-html="link.label"
                class="px-3 py-1.5 rounded-lg text-sm border transition"
                :class="link.active
                    ? 'bg-[#C27B5B] text-white border-[#C27B5B]'
                    : link.url
                        ? 'bg-white dark:bg-[#222220] border-[#EDE5DD] dark:border-[#3D3D39] text-[#6B5C55] dark:text-[#C9B8A6] hover:border-[#C27B5B]'
                        : 'opacity-30 cursor-not-allowed border-[#EDE5DD] dark:border-[#3D3D39]'"
                preserve-scroll
            />
        </div>
    </AdminLayout>
</template>
