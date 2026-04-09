<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
    calories?: number | null
    protein?: number | null
    carbs?: number | null
    fat?: number | null
    servings?: number | null
    source?: 'gpt_estimate' | 'refined' | null
}>()

const hasData = computed(() => {
    return props.calories != null || props.protein != null || props.carbs != null || props.fat != null
})

const sourceLabel = computed(() => {
    if (props.source === 'gpt_estimate') return 'AI Estimate'
    if (props.source === 'refined') return 'Verified'
    return null
})

const isVerified = computed(() => props.source === 'refined')
</script>

<template>
    <div
        v-if="hasData"
        class="rounded-xl bg-white dark:bg-[#222220] border border-[#EDE5DD] dark:border-[#3D3D39] shadow-sm px-5 py-4"
        role="region"
        aria-label="Nutrition facts per serving"
    >
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-serif text-sm font-semibold text-[#3A2520] dark:text-[#F3EDE6]">
                Nutrition per Serving
                <span v-if="servings" class="font-normal text-[#6B5C55] dark:text-[#C9B8A6] text-xs ml-1">({{ servings }} servings)</span>
            </h3>
            <span
                v-if="sourceLabel"
                class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium"
                :class="isVerified
                    ? 'bg-[#7A8C5A] text-white'
                    : 'border border-[#C27B5B] text-[#C27B5B] dark:text-[#D4967E] dark:border-[#D4967E]'"
                :aria-label="'Nutrition data source: ' + sourceLabel"
            >
                <svg v-if="isVerified" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                {{ sourceLabel }}
            </span>
        </div>

        <div class="flex flex-wrap gap-4 sm:gap-6" role="list" aria-label="Macro nutrients">
            <div v-if="calories != null" role="listitem" class="flex flex-col items-center min-w-[60px]">
                <span class="text-lg font-bold text-[#3A2520] dark:text-[#F3EDE6]" :aria-label="calories + ' calories'">{{ calories }}</span>
                <span class="text-xs text-[#6B5C55] dark:text-[#C9B8A6]">cal</span>
            </div>
            <div v-if="protein != null" role="listitem" class="flex flex-col items-center min-w-[60px]">
                <span class="text-lg font-bold text-[#3A2520] dark:text-[#F3EDE6]" :aria-label="protein + ' grams protein'">{{ protein }}g</span>
                <span class="text-xs text-[#6B5C55] dark:text-[#C9B8A6]">protein</span>
            </div>
            <div v-if="carbs != null" role="listitem" class="flex flex-col items-center min-w-[60px]">
                <span class="text-lg font-bold text-[#3A2520] dark:text-[#F3EDE6]" :aria-label="carbs + ' grams carbs'">{{ carbs }}g</span>
                <span class="text-xs text-[#6B5C55] dark:text-[#C9B8A6]">carbs</span>
            </div>
            <div v-if="fat != null" role="listitem" class="flex flex-col items-center min-w-[60px]">
                <span class="text-lg font-bold text-[#3A2520] dark:text-[#F3EDE6]" :aria-label="fat + ' grams fat'">{{ fat }}g</span>
                <span class="text-xs text-[#6B5C55] dark:text-[#C9B8A6]">fat</span>
            </div>
        </div>
    </div>
</template>
