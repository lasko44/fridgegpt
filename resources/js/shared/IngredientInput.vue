<script setup lang="ts">
import { ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import axios from 'axios'
import BarcodeScanner from '@/shared/Barcode/BarcodeScanner.vue'
import { ScanBarcode } from 'lucide-vue-next'

const showBarcodeScanner = ref(false)

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

const ingredient = ref('')
const ingredients = ref<string[]>([])
const inputRef = ref<HTMLInputElement | null>(null)

const form = useForm({
    ingredients: [],
    restrictions: [] as string[],
})

const dietaryOptions = [
    'Vegetarian', 'Vegan', 'Gluten-Free', 'Dairy-Free',
    'Nut-Free', 'Low-Carb', 'Keto', 'Paleo',
    'Halal', 'Kosher', 'Pescatarian',
]

function toggleRestriction(r: string) {
    const idx = form.restrictions.indexOf(r)
    if (idx >= 0) {
        form.restrictions.splice(idx, 1)
    } else {
        form.restrictions.push(r)
    }
}

const defaultSuggestions = [
    'Chicken', 'Rice', 'Pasta', 'Eggs', 'Butter',
    'Garlic', 'Onion', 'Tomato', 'Cheese', 'Potato',
]

const suggestions = ref<string[]>([...defaultSuggestions])

let suggestTimer: ReturnType<typeof setTimeout> | null = null

// Fetch RAG-powered suggestions when ingredients change
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

function addIngredient() {
    const raw = ingredient.value
    // Split on comma to allow batch adding: "chicken, rice, garlic"
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

function generateRecipe() {
    if (ingredients.value.length < 1) return
    form.ingredients = [...ingredients.value]
    form.post(route('recipe.store'))
}

const showDietary = ref(false)

const availableSuggestions = () => {
    return suggestions.value.filter(s => !ingredients.value.some(i => i.toLowerCase() === s.toLowerCase()))
}
</script>

<template>
    <form class="flex flex-col items-center w-full max-w-lg" @submit.prevent="generateRecipe" aria-label="Recipe ingredient form" role="form">
        <!-- Input -->
        <div class="w-full flex mb-3">
            <label for="ingredient-input" class="sr-only">Add ingredients</label>
            <input
                id="ingredient-input"
                ref="inputRef"
                v-model="ingredient"
                @keydown.enter.prevent="addIngredient"
                type="text"
                placeholder="e.g. chicken, rice, garlic"
                aria-label="Type ingredients separated by commas"
                class="w-full rounded-full px-4 py-3 border border-[#D4C8B8] dark:border-[#3D3D39] bg-white dark:bg-[#262624] text-[#3A2520] dark:text-[#F3EDE6] placeholder-[#6B5C55] focus:outline-none focus:ring-2 focus:ring-[#C27B5B] text-base shadow-sm"
            />
            <button
                type="button"
                @click="addIngredient"
                :disabled="!ingredient.trim()"
                aria-label="Add ingredient to list"
                class="ml-2 px-5 py-3 rounded-full bg-[#C27B5B] text-white font-semibold hover:cursor-pointer hover:bg-[#A8664A] focus:outline-none focus:ring-2 focus:ring-[#C27B5B] disabled:opacity-40 disabled:cursor-not-allowed transition"
            >
                Add
            </button>
            <button
                type="button"
                @click="showBarcodeScanner = true"
                aria-label="Scan barcode to add ingredient"
                class="ml-1 flex items-center justify-center h-12 w-12 rounded-full border border-[#D4C8B8] dark:border-[#3D3D39] text-[#6B5C55] dark:text-[#C9B8A6] hover:border-[#C27B5B] hover:text-[#C27B5B] dark:hover:text-[#C27B5B] transition focus:outline-none focus:ring-2 focus:ring-[#C27B5B]"
            >
                <ScanBarcode class="h-5 w-5" aria-hidden="true" />
            </button>
        </div>

        <p class="text-[#6B5C55] dark:text-[#C9B8A6] text-sm mb-4">Separate multiple ingredients with commas</p>

        <!-- Quick-add suggestions -->
        <div v-if="availableSuggestions().length > 0 && ingredients.length < 20" class="flex flex-wrap gap-2 mb-4 justify-center">
            <button
                v-for="s in availableSuggestions().slice(0, 8)"
                :key="s"
                type="button"
                @click="addSuggestion(s)"
                class="px-3 py-1 rounded-full border border-[#D4C8B8] dark:border-[#3D3D39] text-[#6B5C55] dark:text-[#C9B8A6] text-sm hover:border-[#C27B5B] hover:text-[#C27B5B] dark:hover:text-[#C27B5B] hover:bg-white/50 dark:hover:bg-white/5 transition focus:outline-none focus:ring-2 focus:ring-[#C27B5B]"
                :aria-label="'Add ' + s"
            >
                + {{ s }}
            </button>
        </div>

        <!-- Added ingredients -->
        <div v-if="ingredients.length > 0" class="flex flex-wrap gap-2 mb-4 justify-center" role="list" aria-label="Added ingredients">
            <span
                v-for="(ing, idx) in ingredients"
                :key="ing"
                role="listitem"
                class="flex items-center bg-white dark:bg-[#262624] border border-[#D4C8B8] dark:border-[#3D3D39] rounded-full px-4 py-1.5 text-[#3A2520] dark:text-[#F3EDE6] shadow-sm"
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
                class="px-3 py-1 rounded-full text-[#6B5C55] text-sm hover:text-red-500 transition focus:outline-none"
                aria-label="Clear all ingredients"
            >
                Clear all
            </button>
        </div>

        <!-- Dietary Restrictions -->
        <div v-if="ingredients.length > 0" class="w-full mb-4">
            <button
                type="button"
                @click="showDietary = !showDietary"
                class="flex items-center gap-2 text-sm text-[#6B5C55] dark:text-[#C9B8A6] hover:text-[#C27B5B] dark:hover:text-[#C27B5B] transition mx-auto"
                :aria-expanded="showDietary"
                aria-controls="dietary-options"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                </svg>
                Dietary preferences
                <span v-if="form.restrictions.length > 0" class="px-1.5 py-0.5 text-xs bg-[#C27B5B] text-white rounded-full">{{ form.restrictions.length }}</span>
                <svg class="h-3 w-3 transition-transform" :class="{ 'rotate-180': showDietary }" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div v-show="showDietary" id="dietary-options" class="flex flex-wrap gap-2 mt-3 justify-center" role="group" aria-label="Dietary restrictions">
                <button
                    v-for="r in dietaryOptions"
                    :key="r"
                    type="button"
                    @click="toggleRestriction(r)"
                    class="px-3 py-1.5 text-xs rounded-full border transition focus:outline-none focus:ring-2 focus:ring-[#C27B5B]"
                    :class="form.restrictions.includes(r)
                        ? 'bg-[#C27B5B] border-[#C27B5B] text-white'
                        : 'border-[#EDE5DD] dark:border-[#3D3D39] text-[#6B5C55] dark:text-[#C9B8A6] hover:border-[#C27B5B] hover:text-[#C27B5B]'"
                    :aria-pressed="form.restrictions.includes(r)"
                >
                    {{ r }}
                </button>
            </div>
        </div>

        <!-- Counter + Generate -->
        <div class="flex items-center gap-4">
            <span v-if="ingredients.length > 0" class="text-[#6B5C55] dark:text-[#C9B8A6] text-sm">{{ ingredients.length }}/20</span>
            <button
                v-if="ingredients.length >= 1"
                type="submit"
                :disabled="form.processing"
                aria-label="Generate recipe from ingredients"
                class="px-8 py-3 rounded-full bg-[#C27B5B] text-white font-semibold hover:bg-[#A8664A] hover:cursor-pointer focus:outline-none focus:ring-2 focus:ring-[#C27B5B] transition disabled:opacity-50"
            >
                {{ form.processing ? 'Generating...' : 'Generate Recipe' }}
            </button>
        </div>
        <!-- Barcode Scanner Modal -->
        <BarcodeScanner
            v-if="showBarcodeScanner"
            @add-ingredient="handleBarcodeIngredient"
            @close="showBarcodeScanner = false"
        />
    </form>
</template>
