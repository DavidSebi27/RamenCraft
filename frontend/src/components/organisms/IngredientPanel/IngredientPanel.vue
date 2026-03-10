<script setup>
import { reactive } from 'vue'
import CategoryPicker from '@/components/molecules/CategoryPicker/CategoryPicker.vue'

/**
 * IngredientPanel — Contains all 5 CategoryPickers for building a ramen bowl
 *
 * Receives categories and ingredients as props from PlayPage (which fetches from API).
 * Manages selection state for each category as a reactive object.
 * Emits the full selections object whenever any category changes.
 *
 * Selection rules:
 * - Broth: single select (pick one)
 * - Noodles: single select (pick one)
 * - Oil: multi select (pick any number)
 * - Protein: multi select (pick any number)
 * - Topping: multi select (pick any number)
 */
defineProps({
  categories: {
    type: Array,
    required: true,
  },
  ingredientsByCategory: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['update:selections'])

const selections = reactive({
  broth: [],
  noodles: [],
  oil: [],
  protein: [],
  topping: [],
})

const multiSelectCategories = ['oil', 'protein', 'topping']

function onCategoryUpdate(categoryName, newIds) {
  selections[categoryName] = newIds
  emit('update:selections', { ...selections })
}
</script>

<template>
  <div>
    <CategoryPicker
      v-for="category in categories"
      :key="category.id"
      :category="category"
      :ingredients="ingredientsByCategory[category.name] || []"
      :selected-ids="selections[category.name]"
      :multi-select="multiSelectCategories.includes(category.name)"
      @update:selected-ids="onCategoryUpdate(category.name, $event)"
    />
  </div>
</template>
