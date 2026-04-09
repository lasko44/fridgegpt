<template>
    <section
        class="flex flex-col items-center justify-center min-h-[45vh] px-6 py-16 bg-[#FBF5F0] dark:bg-[#1A1A18] text-[#3A2520] dark:text-[#E8E0D4]"
        aria-label="FridgeGPT hero section"
    >
        <h1 class="text-4xl md:text-6xl font-bold font-serif tracking-tight mb-4">
            FridgeGPT
        </h1>
        <p class="text-lg md:text-xl text-[#6B5C55] dark:text-[#C9B8A6] mb-2 text-center">
            The AI Recipe Generator
        </p>
        <p class="text-base text-[#6B5C55] dark:text-[#C9B8A6] mb-8 text-center max-w-md">
            Tell us what ingredients you have and we'll create a delicious recipe for you.
        </p>
        <a
            href="/create"
            class="inline-flex items-center gap-2 rounded-full bg-[#C27B5B] hover:bg-[#A8664A] px-8 py-3.5 text-base font-semibold text-white transition focus:ring-2 focus:ring-[#C27B5B] focus:outline-none"
            aria-label="Start creating a recipe"
        >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Create a Recipe
        </a>
    </section>
</template>
