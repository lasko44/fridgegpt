<script setup lang="ts">
import { ref, watch } from 'vue';

type Ingredient = { name: string };

const props = defineProps<{
    ingredients: Ingredient[];
}>();

const emit = defineEmits<{
    (e: 'close', updated?: Ingredient[]): void;
}>();

const localIngredients = ref<Ingredient[]>([...props.ingredients]);
const newIngredient = ref('');

watch(
    () => props.ingredients,
    (val) => {
        localIngredients.value = [...val];
    },
);

function addIngredient() {
    const value = newIngredient.value.trim();
    if (value && !localIngredients.value.some((i) => i.name === value) && localIngredients.value.length < 20) {
        localIngredients.value.push({ name: value });
        newIngredient.value = '';
    }
}

function removeIngredient(idx: number) {
    localIngredients.value.splice(idx, 1);
}

function save() {
    emit('close', [...localIngredients.value]);
}

function cancel() {
    emit('close');
}
</script>

<template>
    <div class="bg-opacity-40 fixed inset-0 z-50 flex items-center justify-center bg-black/30">
        <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-lg">
            <h2 class="mb-4 text-lg font-bold">Edit Ingredients</h2>
            <div class="mb-4 flex">
                <input
                    v-model="newIngredient"
                    @keydown.enter.prevent="addIngredient"
                    type="text"
                    placeholder="Add ingredient"
                    class="w-full rounded-full border border-gray-300 bg-white px-4 py-2 text-gray-900 focus:ring-2 focus:ring-blue-700 focus:outline-none"
                />
                <button
                    type="button"
                    @click="addIngredient"
                    class="ml-2 rounded-full hover:cursor-pointer bg-blue-800 px-4 py-2 font-semibold text-white hover:bg-blue-900 focus:ring-2 focus:ring-blue-700 focus:outline-none"
                >
                    Add
                </button>
            </div>
            <div class="mb-4 flex flex-wrap gap-2">
                <span
                    v-for="(ing, idx) in localIngredients"
                    :key="ing.name"
                    class="flex items-center rounded-full bg-gray-200 px-4 py-1 text-gray-700"
                >
                    {{ ing.name }}
                    <button
                        @click="removeIngredient(idx)"
                        class="ml-2 text-gray-500 hover:cursor-pointer hover:text-red-500 focus:outline-none"
                        aria-label="Remove ingredient"
                        type="button"
                        tabindex="0"
                    >
                        <span aria-hidden="true">&times;</span>
                    </button>
                </span>
            </div>
            <div class="flex justify-end gap-2">
                <button @click="cancel" class="rounded hover:cursor-pointer bg-gray-300 px-4 py-2 text-gray-700 hover:bg-gray-400">Cancel</button>
                <button @click="save" class="rounded hover:cursor-pointer bg-blue-700 px-4 py-2 text-white hover:bg-blue-800">Save</button>
            </div>
        </div>
    </div>
</template>

