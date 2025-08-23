<script setup lang="ts">
import { ref, watch } from 'vue';

const restrictions = [
    'Vegan',
    'Gluten-Free',
    'Dairy-Free (Lactose Intolerant)',
    'Nut-Free',
    'Halal',
    'Kosher',
    'Shellfish-Free',
];

const selected = ref<string[]>([]);

const emit = defineEmits<{
    (e: 'update:selected', value: string[]): void;
}>();

watch(selected, (val) => {
    emit('update:selected', val);
});
</script>

<template>
    <section>
        <h3 class="mb-4 text-lg font-bold">Dietary Restrictions</h3>
        <div class="flex flex-col gap-4">
            <label
                v-for="restriction in restrictions"
                :key="restriction"
                class="flex items-center gap-3 cursor-pointer"
            >
                <input
                    type="checkbox"
                    :value="restriction"
                    v-model="selected"
                    class="sr-only peer"
                />
                <span
                    class="w-11 h-6 bg-gray-300 rounded-full transition-colors relative
                    peer-checked:bg-gradient-to-r peer-checked:from-teal-500 peer-checked:via-cyan-500 peer-checked:to-blue-500
                    after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full
                    after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-5"
                ></span>
                <span>{{ restriction }}</span>
            </label>
        </div>
    </section>
</template>

<style scoped>
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0,0,0,0);
    white-space: nowrap;
    border-width: 0;
}
</style>
