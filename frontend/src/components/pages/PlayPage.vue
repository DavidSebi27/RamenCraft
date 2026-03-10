<script setup>
/**
 * PlayPage — Main game screen
 *
 * Fetches categories + ingredients from the API on mount.
 * Passes data down to IngredientPanel (for picking) and BowlBuilder (for rendering).
 * The "Serve Bowl" button is visual only — scoring logic comes in Phase 5.
 */
import { ref, onMounted } from 'vue'
import NavBar from '@/components/organisms/NavBar/NavBar.vue'
import BowlBuilder from '@/components/organisms/BowlBuilder/BowlBuilder.vue'
import IngredientPanel from '@/components/organisms/IngredientPanel/IngredientPanel.vue'
import PixelButton from '@/components/atoms/PixelButton/PixelButton.vue'
import { fetchCategories, fetchIngredientsByCategory } from '@/services/api.js'
import { enrichWithColors } from '@/data/ingredients.js'

// API data
const categories = ref([])
const ingredientsByCategory = ref({})
const ingredientMap = ref({})
const loading = ref(true)
const error = ref(null)

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
  served.value = false
}

function serveBowl() {
  // In Phase 5, this will POST to /api/bowls/serve and show scores
  served.value = true
}

onMounted(async () => {
  try {
    const cats = await fetchCategories()
    categories.value = cats.sort((a, b) => a.sortOrder - b.sortOrder)

    // Fetch all ingredients per category in parallel
    const results = await Promise.all(
      cats.map(async (cat) => {
        const raw = await fetchIngredientsByCategory(cat.name)
        const enriched = enrichWithColors(raw)
        return { name: cat.name, ingredients: enriched }
      })
    )

    // Build categoryName → ingredients map and flat id → ingredient map
    const byCategory = {}
    const byId = {}
    results.forEach(({ name, ingredients }) => {
      byCategory[name] = ingredients
      ingredients.forEach(ing => { byId[ing.id] = ing })
    })

    ingredientsByCategory.value = byCategory
    ingredientMap.value = byId
    loading.value = false
  } catch (err) {
    console.error('Failed to load ingredients:', err)
    error.value = 'Failed to load ingredients. Is the backend running?'
    loading.value = false
  }
})
</script>

<template>
  <div class="min-h-screen bg-ramen-darker">
    <NavBar />

    <div class="max-w-6xl mx-auto p-4">
      <h1 class="font-pixel text-lg text-ramen-orange mb-4">Build Your Bowl</h1>

      <!-- Loading state -->
      <div v-if="loading" class="font-pixel text-[10px] text-ramen-cream/50 text-center py-16">
        Loading ingredients...
      </div>

      <!-- Error state -->
      <div v-else-if="error" class="font-pixel text-[10px] text-ramen-red text-center py-16">
        {{ error }}
      </div>

      <!-- Loaded: game layout -->
      <div v-else class="flex flex-col lg:flex-row gap-6">

        <!-- Left: Bowl visualization -->
        <div class="flex flex-col items-center gap-4">
          <BowlBuilder :selections="selections" :ingredient-map="ingredientMap" />

          <PixelButton
            :label="served ? 'SERVED!' : 'SERVE BOWL'"
            :variant="served ? 'secondary' : 'primary'"
            size="lg"
            @click="serveBowl"
          />

          <p v-if="served" class="font-pixel text-[8px] text-ramen-neon text-center">
            Bowl served! Scoring comes in Phase 5.
          </p>
        </div>

        <!-- Right: Ingredient picker -->
        <div class="flex-1 min-w-0">
          <IngredientPanel
            :categories="categories"
            :ingredients-by-category="ingredientsByCategory"
            @update:selections="onSelectionsUpdate"
          />
        </div>
      </div>
    </div>
  </div>
</template>
