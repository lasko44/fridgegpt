<script setup lang="ts">
import { useHead } from '@vueuse/head';
import MyLayout from '@/layouts/MyLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps<{
    tokenBalance: number;
    savedRecipeCount: number;
    userZipCode?: string | null;
    userStoreName?: string | null;
}>();

useHead({ title: 'Plan Your Meals | FridgeGPT' });

// --- Location for grocery pricing ---
const zipCode = ref(props.userZipCode ?? '');
const stores = ref<{ locationId: string; name: string; address: string }[]>([]);
const selectedStore = ref('');
const loadingStores = ref(false);
const hasStore = computed(() => !!props.userStoreName);

async function findStores() {
    const zip = zipCode.value.trim();
    if (!/^\d{5}$/.test(zip)) return;
    loadingStores.value = true;
    try {
        const response = await fetch(`/grocery/stores?zip_code=${zip}`, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!response.ok) throw new Error('Failed');
        const data = await response.json();
        stores.value = data.stores ?? [];
        if (stores.value.length > 0) {
            selectedStore.value = stores.value[0].locationId;
        }
    } catch {
        stores.value = [];
    } finally {
        loadingStores.value = false;
    }
}

// --- Duration ---
const days = ref(7);
function incrementDays() { days.value = Math.min(days.value + 1, 14); }
function decrementDays() { days.value = Math.max(days.value - 1, 1); }

// --- Meals per day ---
const mealOptions = ['Breakfast', 'Lunch', 'Dinner', 'Snack'] as const;
const selectedMeals = ref<string[]>(['Breakfast', 'Lunch', 'Dinner']);

function toggleMeal(meal: string) {
    const idx = selectedMeals.value.indexOf(meal);
    if (idx >= 0) {
        selectedMeals.value.splice(idx, 1);
    } else {
        selectedMeals.value.push(meal);
    }
}

// --- Budget ---
const budget = ref('');

// --- Macro targets ---
const macrosOpen = ref(false);
const calories = ref('');
const protein = ref('');
const carbs = ref('');
const fat = ref('');

// --- Dietary restrictions ---
const dietaryOptions = [
    'Vegetarian', 'Vegan', 'Gluten-Free', 'Dairy-Free',
    'Nut-Free', 'Peanut-Free', 'Soy-Free', 'Egg-Free',
    'Low-Carb', 'Keto', 'Paleo', 'Whole30',
    'Low-Sodium', 'Low-Fat', 'High-Protein', 'Mediterranean',
    'Halal', 'Kosher', 'Pescatarian',
    'Shellfish-Free', 'Sugar-Free', 'FODMAP-Friendly',
    'Diabetic-Friendly', 'Heart-Healthy', 'Anti-Inflammatory',
];
const restrictions = ref<string[]>([]);

function toggleRestriction(r: string) {
    const idx = restrictions.value.indexOf(r);
    if (idx >= 0) {
        restrictions.value.splice(idx, 1);
    } else {
        restrictions.value.push(r);
    }
}

// --- Preferences ---
const preferences = ref('');

// --- Token cost estimate ---
const totalSlots = computed(() => days.value * selectedMeals.value.length);
// Estimate: most slots will be new recipes. Saved recipes may fill a few if they match,
// but GPT usually generates fresh ones. Show the max cost so users aren't surprised.
const estimatedCost = computed(() => totalSlots.value);
const hasEnoughTokens = computed(() => props.tokenBalance >= estimatedCost.value);
const canSubmit = computed(() => selectedMeals.value.length > 0 && hasEnoughTokens.value);

// --- Plan name (optional) ---
const planName = ref('');

// --- Form ---
const form = useForm({
    name: null as string | null,
    days: 7,
    meals_per_day: [] as string[],
    budget: null as string | null,
    target_calories: null as string | null,
    target_protein: null as string | null,
    target_carbs: null as string | null,
    target_fat: null as string | null,
    restrictions: [] as string[],
    preferences: '',
    zip_code: null as string | null,
    store_id: null as string | null,
    store_name: null as string | null,
});

function submitForm() {
    if (!canSubmit.value) return;

    form.name = planName.value.trim() || null;
    form.days = days.value;
    form.meals_per_day = selectedMeals.value.map((m) => m.toLowerCase());
    form.budget = budget.value || null;
    form.target_calories = calories.value || null;
    form.target_protein = protein.value || null;
    form.target_carbs = carbs.value || null;
    form.target_fat = fat.value || null;
    form.restrictions = [...restrictions.value];
    form.preferences = preferences.value;
    form.zip_code = zipCode.value.trim() || null;
    form.store_id = selectedStore.value || null;
    form.store_name = stores.value.find((s) => s.locationId === selectedStore.value)?.name || null;

    form.post('/meal-plans', {
        preserveScroll: true,
    });
}
</script>

<template>
    <MyLayout>
        <div class="mx-auto max-w-2xl px-4 py-10 md:py-14">

            <!-- Header -->
            <div class="mb-8 text-center">
                <h1 class="font-serif text-3xl md:text-4xl font-bold text-[#3A2520] dark:text-[#F3EDE6] tracking-tight">
                    Plan Your Meals
                </h1>
                <div class="mt-3 inline-flex items-center gap-3">
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full bg-[#C27B5B]/10 dark:bg-[#C27B5B]/20 px-3.5 py-1.5 text-sm font-medium text-[#C27B5B] dark:text-[#D4967E]"
                        aria-label="Token balance"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="12" cy="12" r="10" />
                            <path d="M12 6v6l4 2" />
                        </svg>
                        {{ tokenBalance }} tokens
                    </span>
                </div>
            </div>

            <!-- Name Card -->
            <section
                class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl p-6 mb-6"
                aria-label="Plan name"
            >
                <h2 class="font-serif text-xl font-semibold text-[#3A2520] dark:text-[#F3EDE6] mb-4">
                    Name
                </h2>
                <label for="plan-name" class="block text-xs font-medium text-[#6B5C55] dark:text-[#C9B8A6] mb-1.5 uppercase tracking-wider">
                    Plan name (optional)
                </label>
                <input
                    id="plan-name"
                    v-model="planName"
                    type="text"
                    maxlength="100"
                    placeholder="e.g. Summer Cut, Family Dinner Week"
                    class="w-full rounded-xl bg-[#FBF5F0] dark:bg-[#2E2E2B] border border-[#EDE5DD] dark:border-[#3D3D39] px-4 py-3 text-sm text-[#3A2520] dark:text-[#F3EDE6] placeholder:text-[#C9B8A6] dark:placeholder:text-[#6B5C55] focus:outline-none focus:ring-2 focus:ring-[#C27B5B]"
                />
                <p class="mt-1.5 text-xs text-[#6B5C55] dark:text-[#C9B8A6]">
                    Leave blank and we'll name it for you based on your preferences.
                </p>
            </section>

            <!-- Duration Card -->
            <section
                class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl p-6 mb-6"
                aria-label="Plan duration"
            >
                <h2 class="font-serif text-xl font-semibold text-[#3A2520] dark:text-[#F3EDE6] mb-4">
                    Duration
                </h2>
                <div>
                    <label class="block text-xs font-medium text-[#6B5C55] dark:text-[#C9B8A6] mb-1.5 uppercase tracking-wider">
                        Number of days
                    </label>
                    <div class="inline-flex items-center gap-0.5 rounded-xl bg-[#FBF5F0] dark:bg-[#2E2E2B] border border-[#EDE5DD] dark:border-[#3D3D39] px-2 py-1">
                        <button
                            type="button"
                            @click="decrementDays"
                            :disabled="days <= 1"
                            class="h-7 w-7 rounded-lg flex items-center justify-center text-[#C27B5B] hover:bg-[#C27B5B]/10 transition font-bold disabled:opacity-30"
                            aria-label="Decrease days"
                        >
                            &minus;
                        </button>
                        <span
                            class="text-sm font-medium min-w-[3rem] text-center text-[#3A2520] dark:text-[#E8E0D4]"
                            aria-live="polite"
                        >
                            {{ days }} {{ days === 1 ? 'day' : 'days' }}
                        </span>
                        <button
                            type="button"
                            @click="incrementDays"
                            :disabled="days >= 14"
                            class="h-7 w-7 rounded-lg flex items-center justify-center text-[#C27B5B] hover:bg-[#C27B5B]/10 transition font-bold disabled:opacity-30"
                            aria-label="Increase days"
                        >
                            +
                        </button>
                    </div>
                </div>
            </section>

            <!-- Meals Per Day Card -->
            <section
                class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl p-6 mb-6"
                aria-label="Meals per day"
            >
                <h2 class="font-serif text-xl font-semibold text-[#3A2520] dark:text-[#F3EDE6] mb-4">
                    Meals per day
                </h2>
                <div class="flex flex-wrap gap-2" role="group" aria-label="Select meals">
                    <button
                        v-for="meal in mealOptions"
                        :key="meal"
                        type="button"
                        @click="toggleMeal(meal)"
                        class="px-4 py-2 rounded-xl text-sm font-medium border transition-colors focus:outline-none focus:ring-2 focus:ring-[#C27B5B]"
                        :class="selectedMeals.includes(meal)
                            ? 'bg-[#C27B5B] border-[#C27B5B] text-white'
                            : 'bg-white dark:bg-[#2E2E2B] border-[#EDE5DD] dark:border-[#3D3D39] text-[#6B5C55] dark:text-[#C9B8A6] hover:border-[#C27B5B] hover:text-[#C27B5B]'"
                        :aria-pressed="selectedMeals.includes(meal)"
                    >
                        {{ meal }}
                    </button>
                </div>
                <p v-if="selectedMeals.length === 0" class="mt-2 text-sm text-red-500" role="alert">
                    Select at least one meal type.
                </p>
            </section>

            <!-- Budget Card -->
            <section
                class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl p-6 mb-6"
                aria-label="Budget"
            >
                <h2 class="font-serif text-xl font-semibold text-[#3A2520] dark:text-[#F3EDE6] mb-4">
                    Budget
                </h2>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-[#6B5C55] dark:text-[#C9B8A6] text-sm font-medium" aria-hidden="true">$</span>
                    <label for="budget-input" class="sr-only">Weekly budget (optional)</label>
                    <input
                        id="budget-input"
                        v-model="budget"
                        type="number"
                        min="0"
                        step="0.01"
                        placeholder="Weekly budget (optional)"
                        class="w-full rounded-xl pl-8 pr-4 py-3 border border-[#EDE5DD] dark:border-[#3D3D39] bg-[#FBF5F0] dark:bg-[#2E2E2B] text-[#3A2520] dark:text-[#F3EDE6] placeholder-[#6B5C55] focus:outline-none focus:ring-2 focus:ring-[#C27B5B] text-base"
                    />
                </div>
            </section>

            <!-- Grocery Store Card -->
            <section
                class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl p-6 mb-6"
                aria-label="Grocery store"
            >
                <h2 class="font-serif text-xl font-semibold text-[#3A2520] dark:text-[#F3EDE6] mb-1">
                    Grocery Store
                </h2>
                <p class="text-xs text-[#6B5C55] dark:text-[#C9B8A6] mb-4">
                    Set your store to get real prices on the grocery list.
                    <template v-if="hasStore && !stores.length">
                        Currently using <strong>{{ userStoreName }}</strong>.
                    </template>
                </p>

                <div class="flex flex-wrap gap-2 items-end">
                    <div class="flex-1 min-w-[120px]">
                        <label class="text-xs text-[#6B5C55] dark:text-[#C9B8A6] uppercase tracking-wider font-semibold">Zip Code</label>
                        <input
                            v-model="zipCode"
                            type="text"
                            maxlength="5"
                            placeholder="60614"
                            class="mt-1 w-full rounded-xl bg-[#FBF5F0] dark:bg-[#2E2E2B] border border-[#EDE5DD] dark:border-[#3D3D39] px-4 py-3 text-sm text-[#3A2520] dark:text-[#F3EDE6] focus:outline-none focus:ring-2 focus:ring-[#C27B5B]"
                            @keydown.enter.prevent="findStores"
                        />
                    </div>
                    <button
                        type="button"
                        @click="findStores"
                        :disabled="loadingStores || !/^\d{5}$/.test(zipCode.trim())"
                        class="px-5 py-3 rounded-xl text-sm font-semibold bg-[#C27B5B] text-white hover:bg-[#A8664A] disabled:opacity-40 transition"
                    >
                        {{ loadingStores ? 'Finding...' : 'Find Stores' }}
                    </button>
                </div>

                <div v-if="stores.length > 0" class="mt-3">
                    <label class="text-xs text-[#6B5C55] dark:text-[#C9B8A6] uppercase tracking-wider font-semibold">Select Store</label>
                    <select
                        v-model="selectedStore"
                        class="mt-1 w-full rounded-xl bg-[#FBF5F0] dark:bg-[#2E2E2B] border border-[#EDE5DD] dark:border-[#3D3D39] px-4 py-3 text-sm text-[#3A2520] dark:text-[#F3EDE6] focus:outline-none focus:ring-2 focus:ring-[#C27B5B]"
                    >
                        <option v-for="s in stores" :key="s.locationId" :value="s.locationId">
                            {{ s.name }} — {{ s.address }}
                        </option>
                    </select>
                </div>

                <p v-if="hasStore && !stores.length" class="mt-3 text-xs text-[#7A8C5A]">
                    ✓ Your preferred store is saved — prices will be fetched automatically.
                </p>
            </section>

            <!-- Macro Targets Card (collapsible) -->
            <section
                class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl mb-6 overflow-hidden"
                aria-label="Nutrition targets"
            >
                <button
                    type="button"
                    @click="macrosOpen = !macrosOpen"
                    class="w-full flex items-center justify-between p-6 text-left focus:outline-none focus:ring-2 focus:ring-inset focus:ring-[#C27B5B]"
                    :aria-expanded="macrosOpen"
                    aria-controls="macro-targets-panel"
                >
                    <h2 class="font-serif text-xl font-semibold text-[#3A2520] dark:text-[#F3EDE6]">
                        Set nutrition targets
                    </h2>
                    <svg
                        class="h-5 w-5 text-[#6B5C55] dark:text-[#C9B8A6] transition-transform"
                        :class="macrosOpen ? 'rotate-180' : ''"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div
                    v-show="macrosOpen"
                    id="macro-targets-panel"
                    class="px-6 pb-6 space-y-4"
                >
                    <div>
                        <label for="macro-calories" class="block text-xs font-medium text-[#6B5C55] dark:text-[#C9B8A6] mb-1.5 uppercase tracking-wider">
                            Calories per day
                        </label>
                        <input
                            id="macro-calories"
                            v-model="calories"
                            type="number"
                            min="0"
                            placeholder="2000"
                            class="w-full rounded-xl px-4 py-3 border border-[#EDE5DD] dark:border-[#3D3D39] bg-[#FBF5F0] dark:bg-[#2E2E2B] text-[#3A2520] dark:text-[#F3EDE6] placeholder-[#6B5C55] focus:outline-none focus:ring-2 focus:ring-[#C27B5B] text-base"
                        />
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label for="macro-protein" class="block text-xs font-medium text-[#6B5C55] dark:text-[#C9B8A6] mb-1.5 uppercase tracking-wider">
                                Protein g/day
                            </label>
                            <input
                                id="macro-protein"
                                v-model="protein"
                                type="number"
                                min="0"
                                placeholder="150"
                                class="w-full rounded-xl px-4 py-3 border border-[#EDE5DD] dark:border-[#3D3D39] bg-[#FBF5F0] dark:bg-[#2E2E2B] text-[#3A2520] dark:text-[#F3EDE6] placeholder-[#6B5C55] focus:outline-none focus:ring-2 focus:ring-[#C27B5B] text-base"
                            />
                        </div>
                        <div>
                            <label for="macro-carbs" class="block text-xs font-medium text-[#6B5C55] dark:text-[#C9B8A6] mb-1.5 uppercase tracking-wider">
                                Carbs g/day
                            </label>
                            <input
                                id="macro-carbs"
                                v-model="carbs"
                                type="number"
                                min="0"
                                placeholder="200"
                                class="w-full rounded-xl px-4 py-3 border border-[#EDE5DD] dark:border-[#3D3D39] bg-[#FBF5F0] dark:bg-[#2E2E2B] text-[#3A2520] dark:text-[#F3EDE6] placeholder-[#6B5C55] focus:outline-none focus:ring-2 focus:ring-[#C27B5B] text-base"
                            />
                        </div>
                        <div>
                            <label for="macro-fat" class="block text-xs font-medium text-[#6B5C55] dark:text-[#C9B8A6] mb-1.5 uppercase tracking-wider">
                                Fat g/day
                            </label>
                            <input
                                id="macro-fat"
                                v-model="fat"
                                type="number"
                                min="0"
                                placeholder="65"
                                class="w-full rounded-xl px-4 py-3 border border-[#EDE5DD] dark:border-[#3D3D39] bg-[#FBF5F0] dark:bg-[#2E2E2B] text-[#3A2520] dark:text-[#F3EDE6] placeholder-[#6B5C55] focus:outline-none focus:ring-2 focus:ring-[#C27B5B] text-base"
                            />
                        </div>
                    </div>
                </div>
            </section>

            <!-- Dietary Restrictions Card -->
            <section
                class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl p-6 mb-6"
                aria-label="Dietary restrictions"
            >
                <h2 class="font-serif text-xl font-semibold text-[#3A2520] dark:text-[#F3EDE6] mb-4">
                    Dietary Restrictions
                </h2>
                <div class="flex flex-wrap gap-1.5" role="group" aria-label="Dietary restrictions">
                    <button
                        v-for="r in dietaryOptions"
                        :key="r"
                        type="button"
                        @click="toggleRestriction(r)"
                        class="px-2.5 py-1 text-xs rounded-full border transition-colors focus:outline-none focus:ring-2 focus:ring-[#C27B5B]"
                        :class="restrictions.includes(r)
                            ? 'bg-[#C27B5B] border-[#C27B5B] text-white'
                            : 'bg-white dark:bg-[#2E2E2B] border-[#EDE5DD] dark:border-[#3D3D39] text-[#6B5C55] dark:text-[#C9B8A6] hover:border-[#C27B5B] hover:text-[#C27B5B]'"
                        :aria-pressed="restrictions.includes(r)"
                    >
                        {{ r }}
                    </button>
                </div>
            </section>

            <!-- Preferences Card -->
            <section
                class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl p-6 mb-6"
                aria-label="Preferences"
            >
                <h2 class="font-serif text-xl font-semibold text-[#3A2520] dark:text-[#F3EDE6] mb-4">
                    Preferences
                </h2>
                <label for="preferences-input" class="sr-only">Meal preferences</label>
                <input
                    id="preferences-input"
                    v-model="preferences"
                    type="text"
                    placeholder="Any preferences? e.g. Quick meals, Italian food, meal prep friendly"
                    class="w-full rounded-xl px-4 py-3 border border-[#EDE5DD] dark:border-[#3D3D39] bg-[#FBF5F0] dark:bg-[#2E2E2B] text-[#3A2520] dark:text-[#F3EDE6] placeholder-[#6B5C55] focus:outline-none focus:ring-2 focus:ring-[#C27B5B] text-base"
                />
            </section>

            <!-- Token Cost Estimate -->
            <div class="mb-4 text-center">
                <p class="text-sm text-[#6B5C55] dark:text-[#C9B8A6]" aria-live="polite">
                    Up to {{ estimatedCost }} tokens ({{ totalSlots }} meals × 1 token each)
                    <span v-if="savedRecipeCount > 0" class="block text-xs text-[#7A8C5A] mt-1">
                        Some of your {{ savedRecipeCount }} saved recipes may be reused for free
                    </span>
                </p>
            </div>

            <!-- Generate Button -->
            <div class="mb-8">
                <button
                    v-if="hasEnoughTokens || selectedMeals.length === 0"
                    type="button"
                    @click="submitForm"
                    :disabled="!canSubmit || form.processing"
                    class="w-full py-4 rounded-xl bg-[#C27B5B] text-white text-lg font-semibold hover:bg-[#A8664A] hover:cursor-pointer focus:outline-none focus:ring-2 focus:ring-[#C27B5B] focus:ring-offset-2 disabled:opacity-40 disabled:cursor-not-allowed transition"
                    aria-label="Create meal plan"
                >
                    <span v-if="form.processing" class="inline-flex items-center gap-2">
                        <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        Creating...
                    </span>
                    <span v-else>
                        Create Meal Plan &mdash; up to {{ estimatedCost }} tokens
                    </span>
                </button>

                <div
                    v-else
                    class="w-full py-4 rounded-xl bg-[#EDE5DD] dark:bg-[#3D3D39] text-center"
                >
                    <p class="text-[#6B5C55] dark:text-[#C9B8A6] font-medium">
                        Not enough tokens &mdash;
                        <a
                            href="/tokens"
                            class="text-[#C27B5B] hover:underline font-semibold"
                            aria-label="Buy more tokens"
                        >
                            Buy more
                        </a>
                    </p>
                </div>
            </div>

        </div>
    </MyLayout>
</template>
