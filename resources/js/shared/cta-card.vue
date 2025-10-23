<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
</script>

<template>
  <div class="mx-auto flex w-full max-w-md md:max-w-lg flex-col items-center rounded-xl bg-gradient-to-r from-teal-500 via-cyan-500 to-blue-500 p-6 md:p-8 text-white shadow-lg">
    <!-- <img src="/images/logo.webp" alt="Logo" class="mb-4 h-24 w-24 drop-shadow-lg" /> -->
    <h2 class="mb-2 text-2xl md:text-4xl font-extrabold drop-shadow-lg text-center">Go Premium</h2>
    <div class="mb-2 text-xl md:text-2xl font-bold text-yellow-300 drop-shadow-lg">
      $3.99<span class="text-white text-sm md:text-base font-semibold">/month</span>
    </div>
    <p class="mb-6 text-center text-base md:text-lg font-semibold text-white drop-shadow">
      Unlock all features with Premium and enjoy a <span class="font-extrabold text-yellow-300">free 1-week trial</span>!
    </p>
    <ul class="mb-6 w-full text-base md:text-lg font-bold space-y-2">
      <li class="flex items-center">
        <span class="mr-2 text-green-300 drop-shadow">&#10003;</span>
        <span class="drop-shadow">Unlimited recipes</span>
      </li>
      <li class="flex items-center">
        <span class="mr-2 text-green-300 drop-shadow">&#10003;</span>
        <span class="drop-shadow">Portion Control</span>
      </li>
      <li class="flex items-center">
        <span class="mr-2 text-green-300 drop-shadow">&#10003;</span>
        <span class="drop-shadow">Recipe variations</span>
      </li>
      <li class="flex items-center">
        <span class="mr-2 text-green-300 drop-shadow">&#10003;</span>
        <span class="drop-shadow">Dietary Restrictions</span>
      </li>
    </ul>
    <Link
      href="/subscription/create"
      class="w-full md:w-auto text-center rounded-full bg-white px-6 py-2 font-bold text-teal-700 drop-shadow transition hover:bg-blue-100 focus:ring-2 focus:ring-white focus:outline-none"
    >
      Upgrade Now
    </Link>
  </div>
</template>
