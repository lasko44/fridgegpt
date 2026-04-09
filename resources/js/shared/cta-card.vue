<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
</script>

<template>
  <div class="mx-auto w-full max-w-md md:max-w-lg rounded-3xl bg-[#FBF5F0] dark:bg-[#222220] p-8 md:p-10 text-center" role="region" aria-label="Buy tokens offer">
    <p class="text-sm font-medium tracking-widest uppercase text-[#C27B5B] dark:text-[#D4967E] mb-4">Tokens</p>
    <h2 class="text-3xl md:text-4xl font-bold font-serif text-[#3A2520] dark:text-[#EDE5DD] mb-2">Power your kitchen</h2>
    <p class="text-[#6B5C55] dark:text-[#C9B8A6] mb-6 text-base leading-relaxed">
      Buy tokens to generate recipes, create variations, and customize portions. Use them whenever you like.
    </p>
    <ul class="text-left max-w-xs mx-auto mb-8 space-y-3 text-[#3A2520] dark:text-[#EDE5DD]" role="list">
      <li class="flex items-center gap-3" role="listitem">
        <span class="text-[#7A8C5A]" aria-hidden="true">&#10003;</span>
        Generate AI recipes
      </li>
      <li class="flex items-center gap-3" role="listitem">
        <span class="text-[#7A8C5A]" aria-hidden="true">&#10003;</span>
        Recipe variations
      </li>
      <li class="flex items-center gap-3" role="listitem">
        <span class="text-[#7A8C5A]" aria-hidden="true">&#10003;</span>
        Dietary customizations
      </li>
      <li class="flex items-center gap-3" role="listitem">
        <span class="text-[#7A8C5A]" aria-hidden="true">&#10003;</span>
        Tokens never expire
      </li>
    </ul>
    <Link
      href="/tokens"
      class="inline-block rounded-full bg-[#C27B5B] hover:bg-[#A8664A] px-10 py-3 text-white font-semibold transition focus:ring-2 focus:ring-[#C27B5B] focus:outline-none"
      aria-label="Buy tokens"
    >
      Buy Tokens
    </Link>
  </div>
</template>
