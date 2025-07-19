<script setup lang="ts">
// No script needed for static footer
</script>

<template>
    <footer
        class="w-full py-6 px-4 bg-blue-700 text-white flex flex-col md:flex-row items-center justify-between"
        aria-label="Site footer"
    >
    <span class="text-sm md:text-base font-medium" tabindex="0">
      &copy; {{ new Date().getFullYear() }} FridgeGPT. All rights reserved.
    </span>
       <nav class="mt-2 md:mt-0 flex gap-4">
           <a
               href="/privacy-policy"
               class="text-white underline hover:text-cyan-200 focus:outline-none focus:ring-2 focus:ring-cyan-400 rounded transition"
               aria-label="Privacy Policy"
               tabindex="0"
           >
               Privacy Policy
           </a>
           <a
               href="/cookie-policy"
               class="text-white underline hover:text-cyan-200 focus:outline-none focus:ring-2 focus:ring-cyan-400 rounded transition"
               aria-label="Cookie Policy"
               tabindex="0"
           >
               Cookie Policy
           </a>
           <a
               href="/terms-of-service"
               class="text-white underline hover:text-cyan-200 focus:outline-none focus:ring-2 focus:ring-cyan-400 rounded transition"
               aria-label="Terms of Service"
               tabindex="0"
           >
               Terms of Service
           </a>
       </nav>
    </footer>
</template>
