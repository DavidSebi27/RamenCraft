<script setup>
import { reactive, computed } from 'vue'
import { categories, getIngredientsByCategory } from '@/data/ingredients.js'
import CategoryPicker from '@/components/molecules/CategoryPicker/CategoryPicker.vue'

/**
 * IngredientPanel — Contains all 5 CategoryPickers for building a ramen bowl
 *
 * Manages selection state for each category as a reactive object.
 * Emits the full selections object whenever any category changes,
 * so the parent (PlayPage) can pass it to BowlBuilder.
 *
 * Selection rules from the briefing:
 * - Broth: single select (pick one)
 * - Noodles: single select (pick one)
 * - Oil: multi select (pick any number)
 * - Protein: multi select (pick any number)
 * - Topping: multi select (pick any number)
 */
const emit = defineEmits(['update:selections'])

// Reactive selections state — keyed by category name
const selections = reactive({
  broth: [],
  noodles: [],
  oil: [],
  protein: [],
  topping: [],
})

// Which categories allow multiple selections
const multiSelectCategories = ['oil', 'protein', 'topping']

/**
 * Handle when a category's selection changes.
 * Updates local state and emits to parent.
 */
function onCategoryUpdate(categoryName, newIds) {
  selections[categoryName] = newIds
  // Emit a plain object copy of all selections
  emit('update:selections', { ...selections })
}

// Get ingredients for each category, sorted by category sortOrder
const sortedCategories = computed(() =>
  [...categories].sort((a, b) => a.sortOrder - b.sortOrder)
)
</script>

<template>
  <div>
    <CategoryPicker
      v-for="category in sortedCategories"
      :key="category.id"
      :category="category"
      :ingredients="getIngredientsByCategory(category.id)"
      :selected-ids="selections[category.name]"
      :multi-select="multiSelectCategories.includes(category.name)"
      @update:selected-ids="onCategoryUpdate(category.name, $event)"
    />
  </div>
</template>