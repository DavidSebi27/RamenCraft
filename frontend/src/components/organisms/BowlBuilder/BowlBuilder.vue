<script setup>
import { computed } from 'vue'
import { getIngredientById } from '@/data/ingredients.js'

/**
 * BowlBuilder — Interactive bowl visualization with layered sprite system
 *
 * Renders the ramen bowl as stacked layers using absolute positioning + z-index:
 *   z-10: Empty bowl (always visible)
 *   z-20: Broth fill (colored based on selected broth)
 *   z-30: Noodles
 *   z-40: Oil drizzle overlay
 *   z-50: Proteins (positioned in upper area)
 *   z-60: Toppings (scattered positions)
 *
 * Since real sprites aren't ready yet, layers use colored placeholder shapes.
 *
 * Props:
 * - selections: Object with { broth: [id], noodles: [id], oil: [ids], protein: [ids], topping: [ids] }
 */
const props = defineProps({
  selections: {
    type: Object,
    default: () => ({
      broth: [],
      noodles: [],
      oil: [],
      protein: [],
      topping: [],
    }),
  },
})

// Look up the full ingredient objects for each selected ID
const selectedBroth = computed(() =>
  props.selections.broth[0] ? getIngredientById(props.selections.broth[0]) : null
)
const selectedNoodles = computed(() =>
  props.selections.noodles[0] ? getIngredientById(props.selections.noodles[0]) : null
)
const selectedOils = computed(() =>
  props.selections.oil.map(id => getIngredientById(id)).filter(Boolean)
)
const selectedProteins = computed(() =>
  props.selections.protein.map(id => getIngredientById(id)).filter(Boolean)
)
const selectedToppings = computed(() =>
  props.selections.topping.map(id => getIngredientById(id)).filter(Boolean)
)

// Predefined positions for proteins (clock positions in the bowl)
const proteinPositions = [
  { top: '10%', left: '25%' },   // 10 o'clock
  { top: '8%', left: '55%' },    // 1 o'clock
  { top: '20%', left: '65%' },   // 2 o'clock
]

// Predefined positions for toppings (scattered around the bowl)
const toppingPositions = [
  { top: '55%', left: '20%' },   // 7 o'clock
  { top: '60%', left: '45%' },   // 6 o'clock
  { top: '50%', left: '65%' },   // 5 o'clock
  { top: '35%', left: '15%' },   // 9 o'clock
  { top: '40%', left: '70%' },   // 3 o'clock
  { top: '65%', left: '30%' },   // 7-8 o'clock
  { top: '30%', left: '45%' },   // center-top
]
</script>

<template>
  <div class="relative mx-auto" style="width: 280px; height: 280px;">

    <!-- z-10: Empty bowl (always visible) -->
    <div class="absolute inset-0 z-10 flex items-center justify-center">
      <div class="w-64 h-64 rounded-full border-8 border-ramen-brown bg-ramen-dark/50"></div>
    </div>

    <!-- z-20: Broth fill -->
    <div v-if="selectedBroth" class="absolute inset-0 z-20 flex items-center justify-center">
      <div
        class="w-56 h-56 rounded-full transition-colors duration-300"
        :style="{ backgroundColor: selectedBroth.color + 'CC' }"
      ></div>
    </div>

    <!-- z-30: Noodles -->
    <div v-if="selectedNoodles" class="absolute inset-0 z-30 flex items-center justify-center">
      <div class="w-40 h-20 mt-4 rounded-full opacity-80"
        :style="{ backgroundColor: selectedNoodles.color }"
      >
        <div class="w-full h-full flex items-center justify-center">
          <span class="font-pixel text-[7px] text-ramen-dark/70">{{ selectedNoodles.nameJp }}</span>
        </div>
      </div>
    </div>

    <!-- z-40: Oil drizzle overlay(s) -->
    <div
      v-for="(oil, index) in selectedOils"
      :key="'oil-' + oil.id"
      class="absolute inset-0 z-40 flex items-center justify-center"
    >
      <!-- Oil shown as semi-transparent spots on the broth surface -->
      <div
        class="w-48 h-48 rounded-full opacity-30"
        :style="{
          backgroundColor: oil.color,
          transform: `rotate(${index * 72}deg)`,
        }"
      ></div>
    </div>

    <!-- z-50: Proteins -->
    <div
      v-for="(protein, index) in selectedProteins"
      :key="'protein-' + protein.id"
      class="absolute z-50"
      :style="proteinPositions[index % proteinPositions.length]"
    >
      <div
        class="w-12 h-12 rounded-lg flex items-center justify-center font-pixel text-[6px] text-white border border-white/20"
        :style="{ backgroundColor: protein.color }"
      >
        {{ protein.name.slice(0, 4) }}
      </div>
    </div>

    <!-- z-60: Toppings -->
    <div
      v-for="(topping, index) in selectedToppings"
      :key="'topping-' + topping.id"
      class="absolute z-60"
      :style="toppingPositions[index % toppingPositions.length]"
    >
      <div
        class="w-8 h-8 rounded-full flex items-center justify-center font-pixel text-[5px] text-white border border-white/20"
        :style="{ backgroundColor: topping.color }"
      >
        {{ topping.name.slice(0, 2) }}
      </div>
    </div>

    <!-- Empty state hint -->
    <div
      v-if="!selectedBroth && !selectedNoodles"
      class="absolute inset-0 z-70 flex items-center justify-center"
    >
      <span class="font-pixel text-[8px] text-ramen-cream/30 text-center px-8">
        Pick a broth to start building
      </span>
    </div>
  </div>
</template>