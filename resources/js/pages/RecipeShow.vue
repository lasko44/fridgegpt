<script setup lang="ts">
import { useHead } from '@vueuse/head';
import MyLayout from '@/layouts/MyLayout.vue';
import { usePage, router } from '@inertiajs/vue3';
import { computed, provide, ref } from 'vue';
import axios from 'axios';
import VariationMenu from '@/shared/Variation/VariationMenu.vue';
import NutritionCard from '@/shared/Nutrition/NutritionCard.vue';
import { Recipe } from '@/interfaces/recipe';
import { User } from '@/interfaces/user';

const props = defineProps<{
    recipe: Recipe;
}>();

const page = usePage();
const recipe = props.recipe;
provide('recipe', recipe);

const user = page.props.auth.user as unknown as User | null;

const createdAtMessage = computed(() => {
    const ca = props.recipe.created_at?.toLowerCase();
    if (ca === 'today' || ca === 'just now') return props.recipe.created_at;
    return new Date(props.recipe.created_at).toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' });
});

useHead({ title: `${props.recipe.name} | FridgeGPT` });

// Parse the description into structured sections
const parsed = computed(() => {
    const text = (props.recipe.description || '').trim();
    const lines = text.split('\n');

    let title = '';
    let description = '';
    let prepTime = '';
    let cookTime = '';
    let servings = '';
    let ingredients: string[] = [];
    let instructions: { step: number; text: string }[] = [];

    const titleLine = lines[0] || '';
    title = titleLine.replace(/^title:\s*/i, '').trim();

    const ingredientsIdx = lines.findIndex(l => /^ingredients:/i.test(l.trim()));
    const instructionsIdx = lines.findIndex(l => /^(instructions|directions|steps):/i.test(l.trim()));

    const descEnd = ingredientsIdx !== -1 ? ingredientsIdx : instructionsIdx !== -1 ? instructionsIdx : lines.length;
    description = lines.slice(1, descEnd).map(l => l.trim()).filter(l => {
        const prepMatch = l.match(/^prep\s*time:\s*(.+)/i);
        if (prepMatch) { prepTime = prepMatch[1].trim(); return false; }
        const cookMatch = l.match(/^cook\s*time:\s*(.+)/i);
        if (cookMatch) { cookTime = cookMatch[1].trim(); return false; }
        const servMatch = l.match(/^servings?:\s*(.+)/i);
        if (servMatch) { servings = servMatch[1].trim(); return false; }
        return l.length > 0;
    }).join(' ').trim();

    if (ingredientsIdx !== -1) {
        const end = instructionsIdx !== -1 ? instructionsIdx : lines.length;
        for (let i = ingredientsIdx + 1; i < end; i++) {
            const line = lines[i].trim().replace(/^[-*•]\s*/, '');
            if (line.length > 0) ingredients.push(line);
        }
    }

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

    return { title: title || recipe.name, description, prepTime, cookTime, servings, ingredients, instructions };
});

const checkedIngredients = ref<Set<number>>(new Set());
function toggleIngredient(idx: number) {
    const copy = new Set(checkedIngredients.value);
    copy.has(idx) ? copy.delete(idx) : copy.add(idx);
    checkedIngredients.value = copy;
}

const currentStep = ref(0);
const ingredientCount = computed(() => parsed.value.ingredients.length);
const checkedCount = computed(() => checkedIngredients.value.size);

// Portion size
const portionOptions = ['Single', 'Double', 'Triple'] as const;
const activePortionIdx = ref(0); // 0=Single, 1=Double, 2=Triple
const portionMultiplier = computed(() => activePortionIdx.value + 1);

// Servings adjuster
const originalServings = computed(() => {
    const s = props.recipe.servings || parseInt(parsed.value.servings) || 2;
    return Math.max(1, s);
});
const adjustedServings = ref(0); // 0 = use original
const activeServings = computed(() => adjustedServings.value || originalServings.value);
const multiplier = computed(() => (activeServings.value / originalServings.value) * portionMultiplier.value);
const isAdjusted = computed(() => (adjustedServings.value > 0 && adjustedServings.value !== originalServings.value) || activePortionIdx.value > 0);

function incrementServings() {
    if (adjustedServings.value === 0) adjustedServings.value = originalServings.value;
    adjustedServings.value = Math.min(adjustedServings.value + 1, 20);
}
function decrementServings() {
    if (adjustedServings.value === 0) adjustedServings.value = originalServings.value;
    adjustedServings.value = Math.max(adjustedServings.value - 1, 1);
}
function resetServings() {
    adjustedServings.value = 0;
    activePortionIdx.value = 0;
}

// Scale ingredient amounts
function scaleAmount(text: string): string {
    if (multiplier.value === 1) return text;
    // Find numbers in the ingredient text and scale them
    return text.replace(/(\d+\.?\d*)/g, (match) => {
        const num = parseFloat(match);
        const scaled = num * multiplier.value;
        // Show nice fractions
        if (scaled === Math.floor(scaled)) return scaled.toString();
        return scaled.toFixed(1).replace(/\.0$/, '');
    });
}

// Scaled nutrition
// Save adjusted recipe
const savingAdjusted = ref(false);
async function saveAdjusted() {
    savingAdjusted.value = true;
    try {
        const scaledIngredients = parsed.value.ingredients.map(i => scaleAmount(i));
        const { data } = await axios.post('/api/save-adjusted-recipe', {
            original_slug: recipe.slug,
            servings: activeServings.value,
            ingredients: scaledIngredients,
            nutrition: scaledNutrition.value,
        });
        if (data.slug) {
            router.visit(route('recipe.show', { recipe: data.slug }));
        }
    } catch (e) {
        alert('Failed to save adjusted recipe.');
    } finally {
        savingAdjusted.value = false;
    }
}

const scaledNutrition = computed(() => {
    const cal = props.recipe.calories_per_serving;
    if (!cal) return null;
    return {
        calories: Math.round((cal || 0) * multiplier.value),
        protein: Math.round((props.recipe.protein_per_serving || 0) * multiplier.value),
        carbs: Math.round((props.recipe.carbs_per_serving || 0) * multiplier.value),
        fat: Math.round((props.recipe.fat_per_serving || 0) * multiplier.value),
    };
});
</script>

<template>
    <MyLayout>
        <!-- Hero header -->
        <div class="bg-[#FBF5F0] dark:bg-[#1A1A18] text-[#3A2520] dark:text-[#F3EDE6]">
            <div class="mx-auto max-w-5xl px-4 py-10 md:py-14">
                <!-- Variation badge -->
                <div v-if="recipe.is_variation && recipe.parent_recipe" class="mb-3 inline-flex items-center gap-2 rounded-full bg-[#C27B5B]/10 dark:bg-white/10 px-4 py-1.5 text-sm font-medium">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Variation of
                    <a :href="route('recipe.show', { recipe: recipe.parent_recipe.slug })" class="underline hover:text-white/80">{{ recipe.parent_recipe.name }}</a>
                </div>

                <p class="text-sm text-[#6B5C55] dark:text-[#C9B8A6] mb-2">
                    {{ user?.name || 'Guest' }} &middot; {{ createdAtMessage }}
                </p>
                <h1 class="text-3xl md:text-4xl font-bold tracking-tight mb-3">{{ parsed.title }}</h1>
                <p v-if="parsed.description" class="text-gray-300 text-base md:text-lg leading-relaxed max-w-2xl">
                    {{ parsed.description }}
                </p>
                <!-- Meta badges + servings adjuster -->
                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <span v-if="parsed.prepTime" class="inline-flex items-center gap-1.5 rounded-full bg-[#C27B5B]/10 dark:bg-white/10 px-3 py-1 text-sm font-medium">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                        Prep {{ parsed.prepTime }}
                    </span>
                    <span v-if="parsed.cookTime" class="inline-flex items-center gap-1.5 rounded-full bg-[#C27B5B]/10 dark:bg-white/10 px-3 py-1 text-sm font-medium">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48z"/></svg>
                        Cook {{ parsed.cookTime }}
                    </span>
                    <!-- Portion size -->
                    <div class="inline-flex items-center gap-0.5 rounded-full bg-[#C27B5B]/10 dark:bg-white/10 p-0.5" role="radiogroup" aria-label="Portion size">
                        <button
                            v-for="(portion, idx) in portionOptions"
                            :key="portion"
                            @click="activePortionIdx = idx"
                            class="px-3 py-1 rounded-full text-sm font-medium transition"
                            :class="activePortionIdx === idx
                                ? 'bg-[#C27B5B] text-white'
                                : 'text-[#6B5C55] dark:text-[#C9B8A6] hover:text-[#C27B5B]'"
                            role="radio"
                            :aria-checked="activePortionIdx === idx"
                            :aria-label="portion + ' portion'"
                        >
                            {{ portion }}
                        </button>
                    </div>

                    <!-- Servings adjuster -->
                    <div class="inline-flex items-center gap-1 rounded-full bg-[#C27B5B]/10 dark:bg-white/10 px-2 py-0.5">
                        <button
                            @click="decrementServings"
                            class="h-7 w-7 rounded-full flex items-center justify-center text-[#C27B5B] hover:bg-[#C27B5B]/20 transition text-lg font-bold"
                            aria-label="Decrease servings"
                            :disabled="activeServings <= 1"
                        >−</button>
                        <span class="text-sm font-medium min-w-[4rem] text-center">
                            {{ activeServings }} {{ activeServings === 1 ? 'serving' : 'servings' }}
                        </span>
                        <button
                            @click="incrementServings"
                            class="h-7 w-7 rounded-full flex items-center justify-center text-[#C27B5B] hover:bg-[#C27B5B]/20 transition text-lg font-bold"
                            aria-label="Increase servings"
                            :disabled="activeServings >= 20"
                        >+</button>
                    </div>

                    <!-- Reset -->
                    <button
                        v-if="isAdjusted"
                        @click="resetServings"
                        class="text-xs text-[#6B5C55] hover:text-[#C27B5B] transition"
                        aria-label="Reset to original"
                    >Reset</button>
                </div>
            </div>
        </div>

        <div class="mx-auto max-w-5xl px-4 py-8 space-y-6">

            <!-- Nutrition Facts (scales with servings) -->
            <NutritionCard
                v-if="scaledNutrition"
                :calories="scaledNutrition.calories"
                :protein="scaledNutrition.protein"
                :carbs="scaledNutrition.carbs"
                :fat="scaledNutrition.fat"
                :servings="activeServings"
                :source="recipe.nutrition_source as 'gpt_estimate' | 'refined' | undefined"
            />
            <div v-if="isAdjusted" class="flex items-center gap-3 -mt-3">
                <p class="text-xs text-[#6B5C55] dark:text-[#C9B8A6]">
                    Adjusted for {{ activeServings }} {{ activeServings === 1 ? 'serving' : 'servings' }}
                </p>
                <button
                    @click="saveAdjusted"
                    :disabled="savingAdjusted"
                    class="text-xs font-medium text-[#C27B5B] hover:underline transition"
                >
                    {{ savingAdjusted ? 'Saving...' : 'Save as new recipe' }}
                </button>
            </div>

            <!-- Variation Menu (logged-in users with tokens) -->
            <section v-if="user" id="variation-menu">
                <VariationMenu :recipe_slug="recipe.slug" :recipe_description="recipe.description" :ingredients="recipe.ingredients" />
            </section>

            <!-- Two-column layout: Ingredients (left) + Instructions (right) -->
            <div v-if="parsed.ingredients.length || parsed.instructions.length" class="flex flex-col md:flex-row gap-6">

                <!-- Ingredients — sidebar on desktop -->
                <aside
                    v-if="parsed.ingredients.length"
                    class="w-full md:w-80 shrink-0"
                    aria-label="Ingredients"
                >
                    <div class="rounded-xl bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] shadow-sm overflow-hidden md:sticky md:top-6">
                        <div class="px-5 py-4 border-b border-[#EDE5DD] dark:border-[#3D3D39] flex items-center justify-between">
                            <div>
                                <h2 class="text-base font-semibold text-[#3A2520] dark:text-[#F3EDE6]">Ingredients</h2>
                                <p class="text-xs text-[#6B5C55] dark:text-[#C9B8A6]">{{ checkedCount }}/{{ ingredientCount }} ready</p>
                            </div>
                            <span
                                v-if="checkedCount === ingredientCount && ingredientCount > 0"
                                class="text-xs font-medium text-[#C27B5B] dark:text-[#D4967E] bg-[#FFF8F3] dark:bg-[#2E2E2B] px-2 py-0.5 rounded-full"
                            >All set!</span>
                        </div>
                        <ul class="divide-y divide-[#EDE5DD] dark:divide-[#3D3D39]/50">
                            <li
                                v-for="(item, idx) in parsed.ingredients"
                                :key="idx"
                                class="flex items-center gap-3 px-5 py-2.5 cursor-pointer transition-colors hover:bg-[#FBF5F0] dark:hover:bg-[#2E2E2B]/50"
                                role="checkbox"
                                :aria-checked="checkedIngredients.has(idx)"
                                :aria-label="item"
                                tabindex="0"
                                @click="toggleIngredient(idx)"
                                @keydown.space.prevent="toggleIngredient(idx)"
                            >
                                <span
                                    class="flex h-5 w-5 shrink-0 items-center justify-center rounded border-2 transition-colors"
                                    :class="checkedIngredients.has(idx) ? 'border-[#7A8C5A] bg-[#7A8C5A] text-white' : 'border-[#EDE5DD] dark:border-[#3D3D39]'"
                                >
                                    <svg v-if="checkedIngredients.has(idx)" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </span>
                                <span class="text-sm" :class="checkedIngredients.has(idx) ? 'text-gray-400 line-through dark:text-gray-500' : 'text-[#3A2520] dark:text-[#F3EDE6]'">{{ scaleAmount(item) }}</span>
                            </li>
                        </ul>
                    </div>
                </aside>

                <!-- Instructions — main content -->
                <section
                    v-if="parsed.instructions.length"
                    class="flex-1 rounded-xl bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] shadow-sm overflow-hidden"
                    aria-label="Instructions"
                >
                    <div class="px-6 py-4 border-b border-[#EDE5DD] dark:border-[#3D3D39] flex items-center justify-between">
                        <h2 class="text-base font-semibold text-[#3A2520] dark:text-[#F3EDE6]">Instructions</h2>
                        <span class="text-xs text-[#6B5C55] dark:text-[#C9B8A6]">Step {{ currentStep + 1 }} of {{ parsed.instructions.length }}</span>
                    </div>
                    <div>
                        <div
                            v-for="(instruction, idx) in parsed.instructions"
                            :key="instruction.step"
                            class="flex gap-4 px-6 py-4 cursor-pointer transition-colors border-b border-[#EDE5DD] dark:border-[#3D3D39]/50 last:border-b-0"
                            :class="idx === currentStep ? 'bg-[#FFF8F3] dark:bg-[#2E2E2B]' : idx < currentStep ? 'opacity-60' : 'hover:bg-[#FBF5F0] dark:hover:bg-[#2E2E2B]/30'"
                            @click="currentStep = idx"
                        >
                            <span
                                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold transition-colors mt-0.5"
                                :class="idx < currentStep ? 'bg-[#C27B5B] text-white' : idx === currentStep ? 'bg-[#C27B5B] text-white ring-2 ring-[#C27B5B] dark:ring-[#D4967E]' : 'bg-[#EDE5DD] dark:bg-[#3D3D39] text-[#6B5C55] dark:text-[#C9B8A6]'"
                            >
                                <svg v-if="idx < currentStep" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <template v-else>{{ instruction.step }}</template>
                            </span>
                            <p class="text-sm leading-relaxed" :class="idx < currentStep ? 'text-[#6B5C55] dark:text-[#6B5C55]' : 'text-[#3A2520] dark:text-[#F3EDE6]'">
                                {{ instruction.text }}
                            </p>
                        </div>
                    </div>
                    <!-- Navigation -->
                    <div class="flex items-center justify-between px-6 py-4 border-t border-[#EDE5DD] dark:border-[#3D3D39] bg-[#FBF5F0] dark:bg-[#2E2E2B]/60">
                        <button
                            v-if="currentStep > 0"
                            @click="currentStep--"
                            class="px-4 py-2 text-sm font-medium rounded-full border border-[#EDE5DD] dark:border-[#3D3D39] text-[#6B5C55] dark:text-[#C9B8A6] hover:bg-[#EFE7DC] dark:hover:bg-[#2E2E2B] transition"
                        >
                            &larr; Previous
                        </button>
                        <div v-else></div>
                        <button
                            v-if="currentStep < parsed.instructions.length - 1"
                            @click="currentStep++"
                            class="px-5 py-2 text-sm font-medium rounded-full bg-[#C27B5B] text-white hover:bg-[#A8664A] transition"
                        >
                            Next Step &rarr;
                        </button>
                        <span v-else class="flex items-center gap-2 text-[#7A8C5A] dark:text-[#6E7B52] font-medium text-sm">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            All done!
                        </span>
                    </div>
                </section>
            </div>

            <!-- Fallback -->
            <section
                v-if="!parsed.ingredients.length && !parsed.instructions.length"
                class="rounded-xl bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] p-6 shadow-sm"
            >
                <pre class="whitespace-pre-wrap text-sm text-[#3A2520] dark:text-[#F3EDE6] leading-relaxed">{{ recipe.description }}</pre>
            </section>
        </div>
    </MyLayout>
</template>
