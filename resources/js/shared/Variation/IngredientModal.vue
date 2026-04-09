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
        <div class="w-full max-w-md rounded-xl bg-white dark:bg-[#222220] p-6 shadow-xl border border-[#EDE5DD] dark:border-[#3D3D39]">
            <h2 class="mb-4 text-lg font-bold text-[#3A2520] dark:text-[#F3EDE6]">Edit Ingredients</h2>
            <div class="mb-4 flex">
                <input
                    v-model="newIngredient"
                    @keydown.enter.prevent="addIngredient"
                    type="text"
                    placeholder="Add ingredient"
                    class="w-full rounded-full border border-[#EDE5DD] dark:border-[#3D3D39] bg-white dark:bg-gray-700 px-4 py-2 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-[#C27B5B] focus:outline-none"
                />
                <button
                    type="button"
                    @click="addIngredient"
                    class="ml-2 rounded-full hover:cursor-pointer bg-[#C27B5B] px-4 py-2 font-semibold text-white hover:bg-[#A8664A] focus:ring-2 focus:ring-[#C27B5B] focus:outline-none"
                >
                    Add
                </button>
            </div>
            <div class="mb-4 flex flex-wrap gap-2">
                <span
                    v-for="(ing, idx) in localIngredients"
                    :key="ing.name"
                    class="flex items-center rounded-full bg-[#EDE5DD] dark:bg-[#3D3D39] px-4 py-1 text-[#3A2520] dark:text-[#F3EDE6]"
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
                <button @click="cancel" class="rounded-full hover:cursor-pointer bg-[#EDE5DD] dark:bg-[#3D3D39] px-5 py-2 text-[#3A2520] dark:text-[#F3EDE6] hover:bg-gray-300 dark:hover:bg-gray-600 transition">Cancel</button>
                <button @click="save" class="rounded-full hover:cursor-pointer bg-[#C27B5B] px-5 py-2 text-white hover:bg-[#A8664A] transition">Save</button>
            </div>
        </div>
    </div>
</template>

