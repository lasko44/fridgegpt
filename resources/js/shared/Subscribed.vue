<template>
    <div
        class="mx-auto my-12 w-full max-w-md rounded-2xl bg-white p-10 shadow-xl"
        role="region"
        aria-labelledby="subscribed-heading"
    >
        <div class="mb-4 flex justify-center">
            <span class="inline-block rounded bg-teal-500 px-4 py-1 text-base font-semibold text-white shadow" id="subscribed-heading">
                Thank you
            </span>
        </div>
        <h2 class="mb-3 text-center text-2xl font-bold text-teal-700">You are already subscribed.</h2>
        <p class="mb-8 text-center text-gray-700">Enjoy your premium features. Start creating and sharing your favorite recipes!</p>
        <div class="flex justify-center">
            <a
                href="/"
                class="rounded-xl bg-gradient-to-r from-blue-600 to-teal-500 px-6 py-3 text-lg font-bold text-white shadow transition hover:from-blue-700 hover:to-teal-600 focus:outline focus:outline-2 focus:outline-teal-800"
                aria-label="Create a new recipe"
            >
                Create a Recipe
            </a>
        </div>
    </div>
</template>
<script setup lang="ts"></script>