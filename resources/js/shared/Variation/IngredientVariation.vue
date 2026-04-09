<script setup lang="ts">
import { ref, watch } from 'vue';
import IngredientModal from './IngredientModal.vue';

type Ingredient = { name: string };

const props = defineProps<{
    modelValue: Ingredient[];
    ingredients: Ingredient[];
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: Ingredient[]): void;
}>();

// Use default ingredients if modelValue is empty
const localIngredients = ref<Ingredient[]>(
    props.modelValue && props.modelValue.length
        ? [...props.modelValue]
        : [...props.ingredients]
);

watch(
    () => props.modelValue,
    (newVal) => {
        if (
            newVal &&
            JSON.stringify(newVal) !== JSON.stringify(localIngredients.value)
        ) {
            localIngredients.value = [...newVal];
        }
    }
);

watch(localIngredients, (val) => {
    emit('update:modelValue', val);
});

const showModal = ref(false);

function handleModalClose(newIngredients?: Ingredient[]) {
    showModal.value = false;
    if (newIngredients) {
        localIngredients.value = [...newIngredients];
    }
}
</script>

<template>
    <section id="edit-ingredients">
        <div class="mb-4 flex">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Edit Ingredients</h3>
            <button
                class="ml-2 text-xs flex items-center rounded-full font-bold hover:cursor-pointer bg-[#C27B5B] hover:bg-[#A8664A] px-4 py-1 text-white"
                @click="showModal = true"
            >
                Edit
            </button>
        </div>
        <div class="mb-4 flex flex-wrap gap-2">
            <span v-for="(ing) in localIngredients" :key="ing.name" class="flex items-center rounded-full bg-gray-200 dark:bg-gray-700 px-4 py-1 text-gray-700 dark:text-gray-200">
                {{ ing.name }}
            </span>
        </div>
        <IngredientModal
            v-if="showModal"
            :ingredients="localIngredients"
            @close="handleModalClose"
        />
    </section>
</template>