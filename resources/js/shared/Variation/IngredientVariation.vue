<script setup lang="ts">
import { ref, watch } from 'vue';
import IngredientModal from './IngredientModal.vue';

type Ingredient = { name: string };

const props = defineProps<{
    ingredients: Ingredient[];
}>();

const localIngredients = ref<Ingredient[]>([...props.ingredients]);
const ingredient = ref<Ingredient>('');
const showModal = ref(false);

watch(
    () => props.ingredients,
    (newVal) => {
        localIngredients.value = [...newVal];
    },
);

function removeIngredient(index: number) {
    localIngredients.value.splice(index, 1);
}

function handleModalClose(newIngredients?: Ingredient[]) {
    showModal.value = false;
    if (newIngredients) {
        localIngredients.value = newIngredients;
    }
}
</script>

<template>
    <section id="edit-ingredients">
        <div class="mb-4 flex">
            <h3 class="text-lg font-bold">Edit Ingredients</h3>
            <button
                class="ml-2 text-xs flex items-center rounded-full font-bold hover:cursor-pointer bg-blue-700 hover:bg-blue-700/90 px-4 py-1 text-white"
                @click="showModal = true"
            >
                Add
            </button>
        </div>
        <div class="mb-4 flex flex-wrap gap-2">
            <span v-for="(ing, idx) in localIngredients" :key="ing.name" class="flex items-center rounded-full bg-gray-200 px-4 py-1 text-gray-700">
                {{ ing.name }}
                <button
                    @click="removeIngredient(idx)"
                    class="ml-2 text-gray-500 hover:text-red-500 focus:outline-none"
                    aria-label="Remove ingredient"
                    type="button"
                    tabindex="0"
                >
                    <span class="hover:cursor-pointer" aria-hidden="true">&times;</span>
                </button>
            </span>
        </div>
        <IngredientModal
            v-if="showModal"
            :ingredients="localIngredients"
            @close="handleModalClose"
        />
    </section>
</template>
