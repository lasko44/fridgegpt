<template>
    <section
        class="flex flex-col items-center justify-center min-h-[60vh] min-h-[500px] px-4 pt-10 bg-gradient-to-r from-teal-500 via-cyan-500 to-blue-500 text-white"
        aria-label="Fridge gpt hero section"
    >
        <div class="flex flex-col items-center justify-center mb-6 w-full">
            <h1 class="text-4xl md:text-6xl font-extrabold drop-shadow-lg" tabindex="0">
                FridgeGPT
            </h1>
        </div>
        <p class="text-lg md:text-2xl font-medium mb-6 text-center w-full" tabindex="0">
            The AI Recipe Generator
        </p>
        <p class="text-base md:text-lg font-normal text-center w-full" tabindex="0">
            What's in your fridge?
        </p>
        <div class="flex justify-center w-full min-h-[220px]">
            <IngredientInput class="mt-10"/>
        </div>
    </section>
</template>
<script setup lang="ts">
import IngredientInput from '@/shared/IngredientInput.vue';
</script>