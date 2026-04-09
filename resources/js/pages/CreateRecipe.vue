<script setup lang="ts">
import MyLayout from '@/layouts/MyLayout.vue'
import BarcodeScanner from '@/shared/Barcode/BarcodeScanner.vue'
import NutritionCard from '@/shared/Nutrition/NutritionCard.vue'
import { useForm, usePage } from '@inertiajs/vue3'
import { ref, watch, computed } from 'vue'
import axios from 'axios'
import { ScanBarcode } from 'lucide-vue-next'

const props = defineProps<{
    tokenBalance: number
    tokenCost: number
}>()

const page = usePage()

// --- Ingredient management ---
const ingredient = ref('')
const ingredients = ref<string[]>([])
const inputRef = ref<HTMLInputElement | null>(null)
const showBarcodeScanner = ref(false)

const defaultSuggestions = [
    'Chicken', 'Rice', 'Pasta', 'Eggs', 'Butter',
    'Garlic', 'Onion', 'Tomato', 'Cheese', 'Potato',
]
const suggestions = ref<string[]>([...defaultSuggestions])
let suggestTimer: ReturnType<typeof setTimeout> | null = null

watch(ingredients, (current) => {
    if (suggestTimer) clearTimeout(suggestTimer)

    if (current.length === 0) {
        suggestions.value = [...defaultSuggestions]
        return
    }

    suggestTimer = setTimeout(async () => {
        try {
            const { data } = await axios.post('/api/v1/ingredients/suggest', {
                ingredients: current,
            })
            if (data.suggestions?.length > 0) {
                suggestions.value = data.suggestions
            }
        } catch {
            // Silently fall back to defaults
        }
    }, 500)
}, { deep: true })

const availableSuggestions = computed(() => {
    return suggestions.value.filter(
        s => !ingredients.value.some(i => i.toLowerCase() === s.toLowerCase())
    )
})

function addIngredient() {
    const raw = ingredient.value
    const items = raw.split(',').map(s => s.trim()).filter(Boolean)

    for (const item of items) {
        if (
            item &&
            !ingredients.value.some(i => i.toLowerCase() === item.toLowerCase()) &&
            ingredients.value.length < 20
        ) {
            ingredients.value.push(item)
        }
    }
    ingredient.value = ''
    inputRef.value?.focus()
}

function addSuggestion(name: string) {
    if (
        !ingredients.value.some(i => i.toLowerCase() === name.toLowerCase()) &&
        ingredients.value.length < 20
    ) {
        ingredients.value.push(name)
    }
}

function removeIngredient(index: number) {
    ingredients.value.splice(index, 1)
}

function clearAll() {
    ingredients.value = []
    inputRef.value?.focus()
}

function handleBarcodeIngredient(name: string) {
    if (
        name &&
        !ingredients.value.some(i => i.toLowerCase() === name.toLowerCase()) &&
        ingredients.value.length < 20
    ) {
        ingredients.value.push(name)
    }
    showBarcodeScanner.value = false
}

// --- Dietary restrictions ---
const dietaryOptions = [
    'Vegetarian', 'Vegan', 'Gluten-Free', 'Dairy-Free',
    'Nut-Free', 'Peanut-Free', 'Soy-Free', 'Egg-Free',
    'Low-Carb', 'Keto', 'Paleo', 'Whole30',
    'Low-Sodium', 'Low-Fat', 'High-Protein', 'Mediterranean',
    'Halal', 'Kosher', 'Pescatarian',
    'Shellfish-Free', 'Sugar-Free', 'FODMAP-Friendly',
    'Diabetic-Friendly', 'Heart-Healthy', 'Anti-Inflammatory',
]
const restrictions = ref<string[]>([])

function toggleRestriction(r: string) {
    const idx = restrictions.value.indexOf(r)
    if (idx >= 0) {
        restrictions.value.splice(idx, 1)
    } else {
        restrictions.value.push(r)
    }
}

// --- Servings ---
const servings = ref(2)

function incrementServings() {
    servings.value = Math.min(servings.value + 1, 20)
}

function decrementServings() {
    servings.value = Math.max(servings.value - 1, 1)
}

// --- Portion ---
const portionOptions = ['Single', 'Double', 'Triple'] as const
const portion = ref<'Single' | 'Double' | 'Triple'>('Single')

// --- Toggles ---
const includeStaples = ref(false)
const allowExtras = ref(false)

// --- Token check ---
const hasTokens = computed(() => props.tokenBalance >= props.tokenCost)

// --- Form ---
const form = useForm({
    ingredients: [] as string[],
    restrictions: [] as string[],
    servings: 2,
    portion: 'Single' as string,
    include_staples: false,
    allow_extras: false,
})

// --- Result ---
const result = ref<{
    title: string
    description: string
    slug: string
    ingredients: string[]
    instructions: { step: number; text: string }[]
    nutrition: {
        calories?: number | null
        protein?: number | null
        carbs?: number | null
        fat?: number | null
    } | null
} | null>(null)

function submitForm() {
    if (ingredients.value.length < 1 || !hasTokens.value) return

    form.ingredients = [...ingredients.value]
    form.restrictions = [...restrictions.value]
    form.servings = servings.value
    form.portion = portion.value
    form.include_staples = includeStaples.value
    form.allow_extras = allowExtras.value

    form.post(route('recipe.store'), {
        preserveScroll: true,
        onSuccess: (page: any) => {
            const recipe = page.props?.recipe
            if (recipe) {
                // Parse the recipe response into structured data
                const text = (recipe.description || '').trim()
                const lines = text.split('\n')

                let title = ''
                let description = ''
                const parsedIngredients: string[] = []
                const parsedInstructions: { step: number; text: string }[] = []

                const titleLine = lines[0] || ''
                title = titleLine.replace(/^title:\s*/i, '').trim()

                const ingredientsIdx = lines.findIndex((l: string) => /^ingredients:/i.test(l.trim()))
                const instructionsIdx = lines.findIndex((l: string) => /^(instructions|directions|steps):/i.test(l.trim()))

                const descEnd = ingredientsIdx !== -1 ? ingredientsIdx : instructionsIdx !== -1 ? instructionsIdx : lines.length
                description = lines.slice(1, descEnd).map((l: string) => l.trim()).filter((l: string) => l.length > 0).join(' ').trim()

                if (ingredientsIdx !== -1) {
                    const end = instructionsIdx !== -1 ? instructionsIdx : lines.length
                    for (let i = ingredientsIdx + 1; i < end; i++) {
                        const line = lines[i].trim().replace(/^[-*\u2022]\s*/, '')
                        if (line.length > 0) parsedIngredients.push(line)
                    }
                }

                if (instructionsIdx !== -1) {
                    let stepNum = 0
                    for (let i = instructionsIdx + 1; i < lines.length; i++) {
                        const line = lines[i].trim().replace(/^\d+[.)]\s*/, '')
                        if (line.length > 0) {
                            stepNum++
                            parsedInstructions.push({ step: stepNum, text: line })
                        }
                    }
                }

                result.value = {
                    title: title || recipe.name,
                    description,
                    slug: recipe.slug,
                    ingredients: parsedIngredients,
                    instructions: parsedInstructions,
                    nutrition: recipe.calories_per_serving != null ? {
                        calories: recipe.calories_per_serving,
                        protein: recipe.protein_per_serving,
                        carbs: recipe.carbs_per_serving,
                        fat: recipe.fat_per_serving,
                    } : null,
                }
            }
        },
    })
}
</script>

<template>
    <MyLayout>
        <div class="mx-auto max-w-2xl px-4 py-10 md:py-14">

            <!-- Header -->
            <div class="mb-8 text-center">
                <h1 class="font-serif text-3xl md:text-4xl font-bold text-[#3A2520] dark:text-[#F3EDE6] tracking-tight">
                    Create a Recipe
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
                    <span class="text-xs text-[#6B5C55] dark:text-[#C9B8A6]">
                        Uses {{ tokenCost }} token
                    </span>
                </div>
            </div>

            <!-- Ingredients Card -->
            <section
                class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl p-6 mb-6"
                aria-label="Ingredients section"
            >
                <h2 class="font-serif text-xl font-semibold text-[#3A2520] dark:text-[#F3EDE6] mb-4">
                    What do you have?
                </h2>

                <!-- Input row -->
                <div class="flex gap-2 mb-2">
                    <label for="create-ingredient-input" class="sr-only">Add ingredients</label>
                    <input
                        id="create-ingredient-input"
                        ref="inputRef"
                        v-model="ingredient"
                        @keydown.enter.prevent="addIngredient"
                        type="text"
                        placeholder="e.g. chicken, rice, garlic"
                        aria-label="Type ingredients separated by commas"
                        class="flex-1 rounded-xl px-4 py-3 border border-[#EDE5DD] dark:border-[#3D3D39] bg-[#FBF5F0] dark:bg-[#2E2E2B] text-[#3A2520] dark:text-[#F3EDE6] placeholder-[#6B5C55] focus:outline-none focus:ring-2 focus:ring-[#C27B5B] text-base"
                    />
                    <button
                        type="button"
                        @click="addIngredient"
                        :disabled="!ingredient.trim()"
                        aria-label="Add ingredient to list"
                        class="px-5 py-3 rounded-xl bg-[#C27B5B] text-white font-semibold hover:bg-[#A8664A] hover:cursor-pointer focus:outline-none focus:ring-2 focus:ring-[#C27B5B] disabled:opacity-40 disabled:cursor-not-allowed transition"
                    >
                        Add
                    </button>
                    <button
                        type="button"
                        @click="showBarcodeScanner = true"
                        aria-label="Scan barcode to add ingredient"
                        class="flex items-center justify-center h-12 w-12 rounded-xl border border-[#EDE5DD] dark:border-[#3D3D39] text-[#6B5C55] dark:text-[#C9B8A6] hover:border-[#C27B5B] hover:text-[#C27B5B] dark:hover:text-[#C27B5B] transition focus:outline-none focus:ring-2 focus:ring-[#C27B5B]"
                    >
                        <ScanBarcode class="h-5 w-5" aria-hidden="true" />
                    </button>
                </div>

                <p class="text-[#6B5C55] dark:text-[#C9B8A6] text-sm mb-4">
                    Separate multiple with commas
                </p>

                <!-- Quick-add suggestions -->
                <div
                    v-if="availableSuggestions.length > 0 && ingredients.length < 20"
                    class="flex flex-wrap gap-2 mb-4"
                    role="group"
                    aria-label="Suggested ingredients"
                >
                    <button
                        v-for="s in availableSuggestions.slice(0, 8)"
                        :key="s"
                        type="button"
                        @click="addSuggestion(s)"
                        class="px-3 py-1.5 rounded-full border border-[#EDE5DD] dark:border-[#3D3D39] text-[#6B5C55] dark:text-[#C9B8A6] text-sm hover:border-[#C27B5B] hover:text-[#C27B5B] dark:hover:text-[#C27B5B] hover:bg-white/50 dark:hover:bg-white/5 transition focus:outline-none focus:ring-2 focus:ring-[#C27B5B]"
                        :aria-label="'Add ' + s"
                    >
                        + {{ s }}
                    </button>
                </div>

                <!-- Added ingredient chips -->
                <div
                    v-if="ingredients.length > 0"
                    class="flex flex-wrap gap-2 mb-3"
                    role="list"
                    aria-label="Added ingredients"
                >
                    <span
                        v-for="(ing, idx) in ingredients"
                        :key="ing"
                        role="listitem"
                        class="flex items-center bg-[#FBF5F0] dark:bg-[#2E2E2B] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-full px-4 py-1.5 text-[#3A2520] dark:text-[#F3EDE6] text-sm"
                    >
                        {{ ing }}
                        <button
                            @click="removeIngredient(idx)"
                            class="ml-2 text-[#6B5C55] hover:text-red-500 focus:outline-none focus:ring-2 focus:ring-red-400 rounded-full transition"
                            :aria-label="'Remove ' + ing"
                            type="button"
                        >
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </span>
                    <button
                        v-if="ingredients.length > 1"
                        type="button"
                        @click="clearAll"
                        class="px-3 py-1.5 rounded-full text-[#6B5C55] dark:text-[#C9B8A6] text-sm hover:text-red-500 dark:hover:text-red-400 transition focus:outline-none"
                        aria-label="Clear all ingredients"
                    >
                        Clear all
                    </button>
                </div>

                <!-- Counter -->
                <p
                    v-if="ingredients.length > 0"
                    class="text-[#6B5C55] dark:text-[#C9B8A6] text-sm"
                    aria-live="polite"
                >
                    {{ ingredients.length }}/20 ingredients
                </p>
            </section>

            <!-- Options Card -->
            <section
                class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl p-6 mb-6"
                aria-label="Recipe options"
            >
                <h2 class="font-serif text-xl font-semibold text-[#3A2520] dark:text-[#F3EDE6] mb-6">
                    Customize
                </h2>

                <!-- Controls row -->
                <div class="flex flex-wrap items-end gap-5 mb-6">
                    <div>
                        <label class="block text-xs font-medium text-[#6B5C55] dark:text-[#C9B8A6] mb-1.5 uppercase tracking-wider">Servings</label>
                        <div class="inline-flex items-center gap-0.5 rounded-xl bg-[#FBF5F0] dark:bg-[#2E2E2B] border border-[#EDE5DD] dark:border-[#3D3D39] px-2 py-1">
                            <button type="button" @click="decrementServings" :disabled="servings <= 1" class="h-7 w-7 rounded-lg flex items-center justify-center text-[#C27B5B] hover:bg-[#C27B5B]/10 transition font-bold disabled:opacity-30" aria-label="Decrease servings">−</button>
                            <span class="text-sm font-medium min-w-[3rem] text-center text-[#3A2520] dark:text-[#E8E0D4]" aria-live="polite">{{ servings }}</span>
                            <button type="button" @click="incrementServings" :disabled="servings >= 20" class="h-7 w-7 rounded-lg flex items-center justify-center text-[#C27B5B] hover:bg-[#C27B5B]/10 transition font-bold disabled:opacity-30" aria-label="Increase servings">+</button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-[#6B5C55] dark:text-[#C9B8A6] mb-1.5 uppercase tracking-wider">Portion</label>
                        <div class="inline-flex items-center gap-0.5 rounded-xl bg-[#FBF5F0] dark:bg-[#2E2E2B] border border-[#EDE5DD] dark:border-[#3D3D39] p-0.5" role="radiogroup" aria-label="Portion size">
                            <button v-for="opt in portionOptions" :key="opt" type="button" @click="portion = opt" class="px-4 py-1.5 rounded-lg text-sm font-medium transition" :class="portion === opt ? 'bg-[#C27B5B] text-white shadow-sm' : 'text-[#6B5C55] dark:text-[#C9B8A6] hover:text-[#C27B5B]'" role="radio" :aria-checked="portion === opt">{{ opt }}</button>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" @click="includeStaples = !includeStaples" class="flex items-center gap-2 px-3 py-1.5 rounded-full text-xs border transition" :class="includeStaples ? 'border-[#C27B5B] bg-[#C27B5B]/5 text-[#C27B5B] font-medium' : 'border-[#EDE5DD] dark:border-[#3D3D39] text-[#6B5C55] dark:text-[#C9B8A6] hover:border-[#C27B5B]/50'" :aria-pressed="includeStaples">
                            <span class="flex h-3.5 w-3.5 shrink-0 items-center justify-center rounded-sm border transition" :class="includeStaples ? 'border-[#C27B5B] bg-[#C27B5B]' : 'border-[#D4C8B8] dark:border-[#3D3D39]'"><svg v-if="includeStaples" class="h-2 w-2 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></span>
                            Kitchen staples
                        </button>
                        <button type="button" @click="allowExtras = !allowExtras" class="flex items-center gap-2 px-3 py-1.5 rounded-full text-xs border transition" :class="allowExtras ? 'border-[#C27B5B] bg-[#C27B5B]/5 text-[#C27B5B] font-medium' : 'border-[#EDE5DD] dark:border-[#3D3D39] text-[#6B5C55] dark:text-[#C9B8A6] hover:border-[#C27B5B]/50'" :aria-pressed="allowExtras">
                            <span class="flex h-3.5 w-3.5 shrink-0 items-center justify-center rounded-sm border transition" :class="allowExtras ? 'border-[#C27B5B] bg-[#C27B5B]' : 'border-[#D4C8B8] dark:border-[#3D3D39]'"><svg v-if="allowExtras" class="h-2 w-2 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></span>
                            Extra ingredients
                        </button>
                    </div>
                </div>

                <!-- Dietary Restrictions -->
                <div>
                    <label class="block text-xs font-medium text-[#6B5C55] dark:text-[#C9B8A6] mb-2 uppercase tracking-wider">Dietary Restrictions</label>
                    <div class="flex flex-wrap gap-1.5" role="group" aria-label="Dietary restrictions">
                        <button v-for="r in dietaryOptions" :key="r" type="button" @click="toggleRestriction(r)" class="px-2.5 py-1 text-xs rounded-full border transition-colors focus:outline-none focus:ring-2 focus:ring-[#C27B5B]" :class="restrictions.includes(r) ? 'bg-[#C27B5B] border-[#C27B5B] text-white' : 'bg-white dark:bg-[#2E2E2B] border-[#EDE5DD] dark:border-[#3D3D39] text-[#6B5C55] dark:text-[#C9B8A6] hover:border-[#C27B5B] hover:text-[#C27B5B]'" :aria-pressed="restrictions.includes(r)">{{ r }}</button>
                    </div>
                </div>
            </section>

            <!-- Generate Button -->
            <div class="mb-8">
                <button
                    v-if="hasTokens"
                    type="button"
                    @click="submitForm"
                    :disabled="ingredients.length < 1 || form.processing"
                    class="w-full py-4 rounded-xl bg-[#C27B5B] text-white text-lg font-semibold hover:bg-[#A8664A] hover:cursor-pointer focus:outline-none focus:ring-2 focus:ring-[#C27B5B] focus:ring-offset-2 disabled:opacity-40 disabled:cursor-not-allowed transition"
                    aria-label="Generate recipe"
                >
                    <span v-if="form.processing" class="inline-flex items-center gap-2">
                        <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        Generating...
                    </span>
                    <span v-else>
                        Generate Recipe &mdash; {{ tokenCost }} token
                    </span>
                </button>

                <div
                    v-else
                    class="w-full py-4 rounded-xl bg-[#EDE5DD] dark:bg-[#3D3D39] text-center"
                >
                    <p class="text-[#6B5C55] dark:text-[#C9B8A6] font-medium">
                        Out of tokens &mdash;
                        <a
                            :href="route('tokens.index')"
                            class="text-[#C27B5B] hover:underline font-semibold"
                            aria-label="Buy more tokens"
                        >
                            Buy more
                        </a>
                    </p>
                </div>
            </div>

            <!-- Result Section -->
            <section
                v-if="result"
                class="bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] rounded-2xl p-6"
                aria-label="Generated recipe"
            >
                <!-- Title -->
                <h2 class="font-serif text-2xl md:text-3xl font-bold text-[#3A2520] dark:text-[#F3EDE6] mb-3">
                    {{ result.title }}
                </h2>

                <!-- Description -->
                <p
                    v-if="result.description"
                    class="text-[#6B5C55] dark:text-[#C9B8A6] text-base leading-relaxed mb-5"
                >
                    {{ result.description }}
                </p>

                <!-- Nutrition -->
                <NutritionCard
                    v-if="result.nutrition"
                    :calories="result.nutrition.calories"
                    :protein="result.nutrition.protein"
                    :carbs="result.nutrition.carbs"
                    :fat="result.nutrition.fat"
                    :servings="servings"
                    class="mb-5"
                />

                <!-- Ingredients -->
                <div v-if="result.ingredients.length > 0" class="mb-5">
                    <h3 class="font-serif text-lg font-semibold text-[#3A2520] dark:text-[#F3EDE6] mb-3">
                        Ingredients
                    </h3>
                    <ul class="space-y-1.5" role="list" aria-label="Recipe ingredients">
                        <li
                            v-for="(item, idx) in result.ingredients"
                            :key="idx"
                            class="flex items-start gap-2 text-sm text-[#3A2520] dark:text-[#F3EDE6]"
                        >
                            <span class="mt-1.5 h-1.5 w-1.5 rounded-full bg-[#C27B5B] shrink-0" aria-hidden="true"></span>
                            {{ item }}
                        </li>
                    </ul>
                </div>

                <!-- Instructions -->
                <div v-if="result.instructions.length > 0" class="mb-5">
                    <h3 class="font-serif text-lg font-semibold text-[#3A2520] dark:text-[#F3EDE6] mb-3">
                        Instructions
                    </h3>
                    <ol class="space-y-3" role="list" aria-label="Recipe instructions">
                        <li
                            v-for="instruction in result.instructions"
                            :key="instruction.step"
                            class="flex gap-3 text-sm"
                        >
                            <span
                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#C27B5B]/10 text-[#C27B5B] dark:text-[#D4967E] text-xs font-bold mt-0.5"
                                aria-hidden="true"
                            >
                                {{ instruction.step }}
                            </span>
                            <p class="text-[#3A2520] dark:text-[#F3EDE6] leading-relaxed">
                                {{ instruction.text }}
                            </p>
                        </li>
                    </ol>
                </div>

                <!-- View Full Recipe link -->
                <a
                    :href="route('recipe.show', { recipe: result.slug })"
                    class="inline-flex items-center gap-2 text-[#C27B5B] hover:text-[#A8664A] font-semibold text-sm transition"
                    aria-label="View the full recipe page"
                >
                    View Full Recipe
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                </a>
            </section>

        </div>

        <!-- Barcode Scanner Modal -->
        <BarcodeScanner
            v-if="showBarcodeScanner"
            @add-ingredient="handleBarcodeIngredient"
            @close="showBarcodeScanner = false"
        />
    </MyLayout>
</template>
