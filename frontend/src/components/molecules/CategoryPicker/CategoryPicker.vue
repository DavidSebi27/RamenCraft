<script setup>
import IngredientCard from '@/components/molecules/IngredientCard/IngredientCard.vue'

/**
 * CategoryPicker — Displays a category label and a scrollable row of ingredients
 *
 * Handles selection logic: single-select (broth, noodles) or multi-select (toppings).
 * Uses v-model pattern via 'update:selectedIds' emit.
 *
 * Props:
 * - category: Object with { name, displayName }
 * - ingredients: Array of ingredient objects for this category
 * - selectedIds: Array of currently selected ingredient IDs
 * - multiSelect: Whether multiple ingredients can be selected (default false)
 */
const props = defineProps({
  category: {
    type: Object,
    required: true,
  },
  ingredients: {
    type: Array,
    required: true,
  },
  selectedIds: {
    type: Array,
    default: () => [],
  },
  multiSelect: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:selectedIds'])

/**
 * Handle ingredient selection.
 * - Single-select: replace current selection (or deselect if same)
 * - Multi-select: toggle the ingredient in/out of the array
 */
function handleSelect(ingredient) {
  if (props.multiSelect) {
    // Toggle: add if not present, remove if already selected
    const exists = props.selectedIds.includes(ingredient.id)
    const newIds = exists
      ? props.selectedIds.filter(id => id !== ingredient.id)
      : [...props.selectedIds, ingredient.id]
    emit('update:selectedIds', newIds)
  } else {
    // Single select: deselect if clicking same ingredient, otherwise replace
    const newIds = props.selectedIds.includes(ingredient.id) ? [] : [ingredient.id]
    emit('update:selectedIds', newIds)
  }
}
</script>

<template>
  <div class="mb-4">
    <!-- Category label -->
    <h3 class="font-pixel text-xs text-ramen-orange mb-2">
      {{ category.displayName }}
      <span v-if="multiSelect" class="text-ramen-cream/40 text-[8px]">(pick multiple)</span>
    </h3>

    <!-- Scrollable row of ingredient cards -->
    <div class="flex gap-2 overflow-x-auto pb-2">
      <IngredientCard
        v-for="ingredient in ingredients"
        :key="ingredient.id"
        :ingredient="ingredient"
        :selected="selectedIds.includes(ingredient.id)"
        @select="handleSelect"
      />
    </div>
  </div>
</template>