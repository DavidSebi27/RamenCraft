<script setup>
/**
 * PlayPage — Main game screen
 *
 * Layout:
 * - Mobile: Bowl on top, ingredient picker below (stacked)
 * - Desktop: Bowl on the left, ingredient picker on the right (side by side)
 *
 * The IngredientPanel manages selection state internally and emits changes.
 * We pass the selections to BowlBuilder so it renders the correct layers.
 * The "Serve Bowl" button is visual only in Phase 1 — scoring logic comes in Phase 5.
 */
import { ref } from 'vue'
import NavBar from '@/components/organisms/NavBar/NavBar.vue'
import BowlBuilder from '@/components/organisms/BowlBuilder/BowlBuilder.vue'
import IngredientPanel from '@/components/organisms/IngredientPanel/IngredientPanel.vue'
import PixelButton from '@/components/atoms/PixelButton/PixelButton.vue'

// Current bowl selections — updated by IngredientPanel
const selections = ref({
  broth: [],
  noodles: [],
  oil: [],
  protein: [],
  topping: [],
})

// Track if bowl has been "served" (visual feedback only for now)
const served = ref(false)

function onSelectionsUpdate(newSelections) {
  selections.value = newSelections
  served.value = false // Reset served state when ingredients change
}

function serveBowl() {
  // In Phase 5, this will POST to /api/bowls/serve and show scores
  served.value = true
}
</script>

<template>
  <div class="min-h-screen bg-ramen-darker">
    <NavBar />

    <div class="max-w-6xl mx-auto p-4">
      <h1 class="font-pixel text-lg text-ramen-orange mb-4">Build Your Bowl</h1>

      <!-- Responsive layout: stacked on mobile, side-by-side on desktop -->
      <div class="flex flex-col lg:flex-row gap-6">

        <!-- Left: Bowl visualization -->
        <div class="flex flex-col items-center gap-4">
          <BowlBuilder :selections="selections" />

          <!-- Serve button -->
          <PixelButton
            :label="served ? 'SERVED!' : 'SERVE BOWL'"
            :variant="served ? 'secondary' : 'primary'"
            size="lg"
            @click="serveBowl"
          />

          <!-- Served feedback -->
          <p v-if="served" class="font-pixel text-[8px] text-ramen-neon text-center">
            Bowl served! Scoring comes in Phase 5.
          </p>
        </div>

        <!-- Right: Ingredient picker -->
        <div class="flex-1 min-w-0">
          <IngredientPanel @update:selections="onSelectionsUpdate" />
        </div>
      </div>
    </div>
  </div>
</template>