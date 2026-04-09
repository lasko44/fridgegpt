<script setup lang="ts">
import { ref, watch, nextTick, computed, onMounted } from 'vue';
import MyLayout from '../layouts/MyLayout.vue';
import { useHead } from '@vueuse/head';
import CtaCard from '../shared/cta-card.vue';
import Hero from '@/shared/Hero.vue';
import RecipeList from '../shared/RecipeList/RecipeList.vue';
import ErrorModal from '@/shared/419ErrorModal.vue';
import { usePage, Link as InertiaLink } from '@inertiajs/vue3';

useHead({
    title: 'FridgeGPT AI Recipe Creator',
    meta: [
        { name: 'description', content: 'Create delicious recipes with FridgeGPT using ingredients you have at home.' },
        { name: 'keywords', content: 'recipe, AI, cooking, ingredients, fridge' },
        { name: 'viewport', content: 'width=device-width, initial-scale=1.0' },
    ],
});
const props = defineProps<{
    recipe?: string;
    structured?: any;
    recipes?: any[] | { data: any[]; [key: string]: any };
    tokenBalance?: number;
    tokenCost?: number;
}>();
const page = usePage();

const recipeSection = ref<HTMLElement | null>(null);

watch(
    () => props.recipe,
    async (val) => {
        if (val) {
            await nextTick();
            recipeSection.value?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    },
);

const showModal = ref(false);
const isLoggedIn = computed(() => !!page.props?.auth?.user);
const tokenBalance = computed(() => props.tokenBalance ?? (page.props?.auth?.user as any)?.token_balance ?? 0);
const tokenCost = computed(() => props.tokenCost ?? 1);
const hasTokens = computed(() => tokenBalance.value >= tokenCost.value);

const hasRecipes = computed(() => {
    if (!props.recipes) return false;
    if (Array.isArray(props.recipes)) return props.recipes.length > 0;
    return Array.isArray(props.recipes.data) && props.recipes.data.length > 0;
});

// Watch for guest_limit error and show/hide modal
watch(
    () => page.props?.errors?.error,
    (val) => {
        showModal.value = !!val;
    },
    { immediate: true },
);

// Clear guest_limit error when modal closes
function handleModalClose() {
    showModal.value = false;
}

// Recipe parsing
const parsedRecipe = computed(() => {
    if (!props.recipe && !props.structured) return null;

    // Use structured JSON from API if available
    if (props.structured?.title) {
        const s = props.structured;
        return {
            title: s.title,
            description: s.description || '',
            prepTime: s.prep_time || '',
            cookTime: s.cook_time || '',
            servings: s.servings ? String(s.servings) : '',
            ingredients: (s.ingredients || []).map((i: any) => [i.amount, i.unit, i.name].filter(Boolean).join(' ')),
            instructions: (s.instructions || []).map((i: any, idx: number) => ({ step: i.step || idx + 1, text: i.text })),
        };
    }

    if (!props.recipe) return null;
    const text = props.recipe.trim();
    let title = '';
    let description = '';
    let prepTime = '';
    let cookTime = '';
    let servings = '';
    let ingredients: string[] = [];
    let instructions: { step: number; text: string }[] = [];

    // Split into lines for parsing
    const lines = text.split('\n');

    // Extract title (first line, optionally prefixed with "Title:")
    const titleLine = lines[0] || '';
    title = titleLine.replace(/^title:\s*/i, '').trim();

    // Identify section boundaries
    const ingredientsIdx = lines.findIndex((l) => /^ingredients:/i.test(l.trim()));
    const instructionsIdx = lines.findIndex((l) => /^(instructions|directions|steps):/i.test(l.trim()));

    // Extract description: lines between title and the first known section
    const descEnd = ingredientsIdx !== -1 ? ingredientsIdx : instructionsIdx !== -1 ? instructionsIdx : lines.length;
    description = lines
        .slice(1, descEnd)
        .map((l) => l.trim())
        .filter((l) => {
            // Pull out meta lines while building description
            const prepMatch = l.match(/^prep\s*time:\s*(.+)/i);
            if (prepMatch) { prepTime = prepMatch[1].trim(); return false; }
            const cookMatch = l.match(/^cook\s*time:\s*(.+)/i);
            if (cookMatch) { cookTime = cookMatch[1].trim(); return false; }
            const servingsMatch = l.match(/^servings?:\s*(.+)/i);
            if (servingsMatch) { servings = servingsMatch[1].trim(); return false; }
            const totalMatch = l.match(/^total\s*time:\s*(.+)/i);
            if (totalMatch && !cookTime) { cookTime = totalMatch[1].trim(); return false; }
            return l.length > 0;
        })
        .join(' ')
        .trim();

    // Extract ingredients
    if (ingredientsIdx !== -1) {
        const end = instructionsIdx !== -1 ? instructionsIdx : lines.length;
        for (let i = ingredientsIdx + 1; i < end; i++) {
            const line = lines[i].trim().replace(/^[-*•]\s*/, '');
            if (line.length > 0) ingredients.push(line);
        }
    }

    // Extract instructions
    if (instructionsIdx !== -1) {
        let stepNum = 0;
        for (let i = instructionsIdx + 1; i < lines.length; i++) {
            const line = lines[i].trim().replace(/^\d+[.)]\s*/, '');
            if (line.length > 0) {
                stepNum++;
                instructions.push({ step: stepNum, text: line });
            }
        }
    }

    // Fallback: if no sections detected, treat everything after title as description
    if (ingredients.length === 0 && instructions.length === 0) {
        description = lines.slice(1).map((l) => l.trim()).filter(Boolean).join('\n');
    }

    return { title, description, prepTime, cookTime, servings, ingredients, instructions };
});

const checkedIngredients = ref<Set<number>>(new Set());

function toggleIngredient(index: number) {
    const copy = new Set(checkedIngredients.value);
    if (copy.has(index)) copy.delete(index);
    else copy.add(index);
    checkedIngredients.value = copy;
}

// 419 error modal logic
const show419ErrorModal = ref(page.props.show419ErrorModal || false);

function handle419ModalClose() {
    show419ErrorModal.value = false;
}

// Timezone detection and redirect if not present in URL
onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    if (!params.has('timezone')) {
        const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
        window.location.replace(
            window.location.pathname + '?timezone=' + encodeURIComponent(timezone)
        );
    }
});
</script>

<template>
    <MyLayout>
        <Hero />

        <main aria-label="Recipe content" class="text-gray-900 dark:text-gray-100">
            <div class="mx-auto max-w-3xl px-4 py-8 space-y-8">

                <!-- 1. Generated Recipe (top priority when present) -->
                <section
                    v-if="parsedRecipe"
                    ref="recipeSection"
                    aria-label="Generated recipe"
                    class="overflow-hidden rounded-xl bg-white text-left shadow-lg dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#2E2E2B]"
                >
                    <div class="border-b border-[#EDE5DD] px-6 pb-5 pt-6 dark:border-[#3D3D39]">
                        <h2 class="text-3xl font-bold tracking-tight">{{ parsedRecipe.title }}</h2>
                        <p v-if="parsedRecipe.description" class="mt-2 text-base leading-relaxed text-[#6B5C55] dark:text-[#C9B8A6]">
                            {{ parsedRecipe.description }}
                        </p>
                        <div
                            v-if="parsedRecipe.prepTime || parsedRecipe.cookTime || parsedRecipe.servings"
                            class="mt-4 flex flex-wrap gap-3 text-sm"
                        >
                            <span v-if="parsedRecipe.prepTime" class="inline-flex items-center gap-1.5 rounded-full bg-[#FFF8F3] px-3 py-1 font-medium text-[#C27B5B] dark:bg-[#2E2E2B] dark:text-[#D4967E]">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                                Prep: {{ parsedRecipe.prepTime }}
                            </span>
                            <span v-if="parsedRecipe.cookTime" class="inline-flex items-center gap-1.5 rounded-full bg-[#FFF8F3] px-3 py-1 font-medium text-[#C27B5B] dark:bg-[#2E2E2B] dark:text-[#D4967E]">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48z"/></svg>
                                Cook: {{ parsedRecipe.cookTime }}
                            </span>
                            <span v-if="parsedRecipe.servings" class="inline-flex items-center gap-1.5 rounded-full bg-[#FFF8F3] px-3 py-1 font-medium text-[#C27B5B] dark:bg-[#2E2E2B] dark:text-[#D4967E]">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128H9m6 0a5.97 5.97 0 0 0-.786-3.07"/></svg>
                                Serves {{ parsedRecipe.servings }}
                            </span>
                        </div>
                    </div>

                    <div v-if="parsedRecipe.ingredients.length" class="border-b border-[#EDE5DD] px-6 py-5 dark:border-[#3D3D39]">
                        <h3 class="mb-3 text-lg font-semibold">Ingredients</h3>
                        <ul class="space-y-2" aria-label="Ingredients list">
                            <li
                                v-for="(item, idx) in parsedRecipe.ingredients"
                                :key="idx"
                                class="flex cursor-pointer items-center gap-3 rounded-lg px-2 py-1.5 transition-colors hover:bg-[#FBF5F0] dark:hover:bg-[#2E2E2B]/60"
                                role="checkbox"
                                :aria-checked="checkedIngredients.has(idx)"
                                :aria-label="item"
                                tabindex="0"
                                @click="toggleIngredient(idx)"
                                @keydown.space.prevent="toggleIngredient(idx)"
                                @keydown.enter.prevent="toggleIngredient(idx)"
                            >
                                <span
                                    class="flex h-5 w-5 shrink-0 items-center justify-center rounded border-2 transition-colors"
                                    :class="checkedIngredients.has(idx)
                                        ? 'border-[#7A8C5A] bg-[#7A8C5A] text-white'
                                        : 'border-[#EDE5DD] dark:border-[#3D3D39]'"
                                >
                                    <svg v-if="checkedIngredients.has(idx)" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </span>
                                <span :class="{ 'text-[#6B5C55] line-through dark:text-[#6B5C55]': checkedIngredients.has(idx) }">{{ item }}</span>
                            </li>
                        </ul>
                    </div>

                    <div v-if="parsedRecipe.instructions.length" class="px-6 py-5">
                        <h3 class="mb-4 text-lg font-semibold">Instructions</h3>
                        <ol class="space-y-4" aria-label="Step-by-step instructions">
                            <li
                                v-for="instruction in parsedRecipe.instructions"
                                :key="instruction.step"
                                class="flex gap-4 rounded-lg bg-[#FBF5F0] p-4 dark:bg-[#2E2E2B]/60"
                            >
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#C27B5B] text-sm font-bold text-white">
                                    {{ instruction.step }}
                                </span>
                                <p class="pt-1 leading-relaxed">{{ instruction.text }}</p>
                            </li>
                        </ol>
                    </div>
                </section>

                <!-- 2. Recipe List (past recipes) -->
                <section v-if="hasRecipes" aria-label="Your saved recipes">
                    <RecipeList :recipes="props.recipes" />
                </section>

                <!-- 3. Welcome + CTA (only when no recipes and no generated recipe) -->
                <section v-if="!hasRecipes && !parsedRecipe" class="text-center space-y-6 py-8">
                    <h2 class="text-3xl font-extrabold">FridgeGPT Recipe Creator</h2>
                    <p class="mx-auto max-w-xl text-base text-[#6B5C55] dark:text-[#C9B8A6]">
                        Enter the ingredients you have above and we'll create a delicious recipe for you.
                    </p>
                    <!-- Token info for logged-in users -->
                    <div v-if="isLoggedIn" class="text-sm text-[#6B5C55] dark:text-[#C9B8A6]">
                        Uses {{ tokenCost }} token &middot; {{ tokenBalance }} remaining
                    </div>
                    <div v-if="isLoggedIn && !hasTokens" class="rounded-xl bg-[#FFF8F3] dark:bg-[#2E2E2B] border border-[#EDE5DD] dark:border-[#3D3D39] px-6 py-5 text-center">
                        <p class="text-[#3A2520] dark:text-[#EDE5DD] font-semibold mb-2">Out of tokens</p>
                        <InertiaLink href="/tokens" class="inline-block rounded-full bg-[#C27B5B] hover:bg-[#A8664A] px-6 py-2 text-white font-semibold transition">
                            Buy more tokens
                        </InertiaLink>
                    </div>
                    <CtaCard v-if="!isLoggedIn" />
                </section>

                <!-- 4. Buy more tokens prompt (shown after generating when tokens low) -->
                <div v-if="parsedRecipe && isLoggedIn && tokenBalance < 3" class="text-center py-4">
                    <p class="text-sm text-[#6B5C55] dark:text-[#C9B8A6] mb-2">Running low on tokens?</p>
                    <InertiaLink href="/tokens" class="inline-block rounded-full bg-[#C27B5B] hover:bg-[#A8664A] px-6 py-2 text-white font-semibold transition text-sm">
                        Buy more tokens
                    </InertiaLink>
                </div>
            </div>
        </main>

        <ErrorModal v-if="show419ErrorModal" @close="handle419ModalClose" />
    </MyLayout>
</template>