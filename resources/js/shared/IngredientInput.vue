<script setup lang="ts">
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'

const ingredient = ref('')
const ingredients = ref<string[]>([])

const form = useForm({
    ingredients: []
})

function addIngredient() {
    const value = ingredient.value.trim()
    if (
        value &&
        !ingredients.value.includes(value) &&
        ingredients.value.length < 20
    ) {
        ingredients.value.push(value)
        ingredient.value = ''
    }
}

function removeIngredient(index: number) {
    ingredients.value.splice(index, 1)
}

function generateRecipe() {
    if (ingredients.value.length < 3) return
    form.ingredients = [...ingredients.value]
    form.post(route('recipe.store'))
}
</script>

<template>
    <form class="flex flex-col items-center" @submit.prevent="generateRecipe">
        <div class="w-96 flex mb-4">
            <input
                v-model="ingredient"
                @keydown.enter.prevent="addIngredient"
                type="text"
                placeholder="Add ingredient"
                class="w-full rounded-full px-4 py-2 border border-gray-800 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-700"
            />
            <button
                type="button"
                @click="addIngredient"
                class="ml-2 px-4 py-2 rounded-full bg-blue-800 text-white font-semibold hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-700"
            >
                Add
            </button>
        </div>
        <div class="flex flex-wrap gap-2 mb-4">
            <span
                v-for="(ing, idx) in ingredients"
                :key="ing"
                class="flex items-center bg-gray-200 rounded-full px-4 py-1 text-gray-700"
            >
                {{ ing }}
                <button
                    @click="removeIngredient(idx)"
                    class="ml-2 text-gray-500 hover:text-red-500 focus:outline-none"
                    aria-label="Remove ingredient"
                    type="button"
                    tabindex="0"
                >
                    <span aria-hidden="true">&times;</span>
                </button>
            </span>
        </div>
        <button
            v-if="ingredients.length >= 3"
            type="submit"
            :disabled="form.processing"
            class="px-6 py-2 rounded-full bg-green-700 text-white font-semibold hover:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 mt-2"
        >
            {{ form.processing ? 'Submitting...' : 'Generate Recipe' }}
        </button>
    </form>
</template>