<script setup lang="ts">
import { useHead } from '@vueuse/head';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import ConfirmDialog from '@/shared/ConfirmDialog.vue';

function debounce<T extends (...args: any[]) => void>(fn: T, ms: number): T {
    let t: ReturnType<typeof setTimeout> | null = null;
    return ((...args: any[]) => {
        if (t) clearTimeout(t);
        t = setTimeout(() => fn(...args), ms);
    }) as T;
}

interface Recipe {
    id: number;
    name: string;
    slug: string;
    is_variation: boolean;
    created_at: string;
    user: { uuid: string; name: string; email: string } | null;
}

interface Props {
    recipes: {
        data: Recipe[];
        current_page: number;
        last_page: number;
        total: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: { search: string | null };
}

const props = defineProps<Props>();
useHead({ title: 'Recipes | Admin' });

const search = ref(props.filters.search ?? '');

const update = debounce(() => {
    router.get('/admin/recipes', { search: search.value || undefined }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}, 300);

watch(search, update);

const recipeToDelete = ref<Recipe | null>(null);

function askDelete(r: Recipe) {
    recipeToDelete.value = r;
}

function confirmDelete() {
    if (! recipeToDelete.value) return;
    router.delete(`/admin/recipes/${recipeToDelete.value.id}`, {
        preserveScroll: true,
        onFinish: () => (recipeToDelete.value = null),
    });
}

function formatDate(d: string): string {
    return new Date(d).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}
</script>

<template>
    <AdminLayout>
        <ConfirmDialog
            :open="recipeToDelete !== null"
            title="Delete recipe?"
            :message="recipeToDelete ? `${recipeToDelete.name} will be permanently removed.` : ''"
            confirm-label="Delete"
            variant="danger"
            @confirm="confirmDelete"
            @cancel="recipeToDelete = null"
        />
        <header class="mb-6">
            <h1 class="font-serif text-3xl font-bold text-[#3A2520] dark:text-[#F3EDE6]">Recipes</h1>
            <p class="text-sm text-[#6B5C55] dark:text-[#C9B8A6] mt-1">{{ recipes.total.toLocaleString() }} total</p>
        </header>

        <div class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-xl p-4 mb-4">
            <input
                v-model="search"
                type="search"
                placeholder="Search by name or ingredients..."
                class="w-full rounded-lg bg-[#FBF5F0] dark:bg-[#2E2E2B] border border-[#EDE5DD] dark:border-[#3D3D39] px-3 py-2 text-sm text-[#3A2520] dark:text-[#F3EDE6] focus:outline-none focus:ring-2 focus:ring-[#C27B5B]"
            />
        </div>

        <div class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-[#FBF5F0] dark:bg-[#2E2E2B] text-[#6B5C55] dark:text-[#C9B8A6] text-xs uppercase">
                    <tr>
                        <th class="text-left px-5 py-3">Recipe</th>
                        <th class="text-left px-5 py-3 hidden md:table-cell">User</th>
                        <th class="text-left px-5 py-3 hidden lg:table-cell">Created</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#EDE5DD] dark:divide-[#3D3D39]">
                    <tr v-for="r in recipes.data" :key="r.id" class="hover:bg-[#FBF5F0] dark:hover:bg-[#2E2E2B]">
                        <td class="px-5 py-3">
                            <a :href="`/recipe/${r.slug}`" target="_blank" class="text-sm font-medium text-[#3A2520] dark:text-[#F3EDE6] hover:text-[#C27B5B]">
                                {{ r.name }}
                            </a>
                            <span v-if="r.is_variation" class="ml-2 px-1.5 py-0.5 rounded-full text-[10px] bg-[#C27B5B]/10 text-[#C27B5B]">variation</span>
                        </td>
                        <td class="px-5 py-3 hidden md:table-cell">
                            <Link v-if="r.user" :href="`/admin/users/${r.user.uuid}`" class="text-xs text-[#6B5C55] dark:text-[#C9B8A6] hover:text-[#C27B5B]">
                                {{ r.user.name }}
                            </Link>
                        </td>
                        <td class="px-5 py-3 text-xs text-[#6B5C55] dark:text-[#C9B8A6] hidden lg:table-cell">{{ formatDate(r.created_at) }}</td>
                        <td class="px-5 py-3 text-right">
                            <button @click="askDelete(r)" class="text-xs text-red-600 hover:underline">delete</button>
                        </td>
                    </tr>
                    <tr v-if="recipes.data.length === 0">
                        <td colspan="4" class="px-5 py-12 text-center text-[#6B5C55] dark:text-[#C9B8A6]">No recipes found</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="recipes.last_page > 1" class="mt-4 flex flex-wrap gap-1.5 justify-center">
            <Link
                v-for="link in recipes.links"
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
