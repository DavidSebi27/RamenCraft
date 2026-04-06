<script setup>
import IngredientIcon from '@/components/atoms/IngredientIcon/IngredientIcon.vue'

/**
 * IngredientCard — Displays a single ingredient with icon, name, and selection state
 *
 * When clicked, emits 'select' so the parent can update selection state.
 * The visual highlight (golden border) is controlled by the 'selected' prop.
 *
 * Props:
 * - ingredient: Object with { name, nameJp, spriteIcon, color }
 * - selected: Whether this ingredient is currently selected
 */
defineProps({
  ingredient: {
    type: Object,
    required: true,
  },
  selected: {
    type: Boolean,
    default: false,
  },
})

import { playSound } from '@/utils/sounds'

const emit = defineEmits(['select'])

function handleSelect(ingredient) {
  playSound('select', 0.2)
  emit('select', ingredient)
}
</script>

<template>
  <button
    class="flex flex-col items-center gap-1.5 p-2.5 border-2 transition-all cursor-pointer w-[100px] h-[140px] flex-shrink-0"
    :class="selected
      ? 'border-ramen-gold bg-ramen-dark shadow-[0_0_8px_rgba(255,215,0,0.3)]'
      : 'border-ramen-brown bg-ramen-darker hover:border-ramen-cream/40'
    "
    @click="handleSelect(ingredient)"
  >
    <IngredientIcon
      :src="ingredient.spriteIcon"
      :alt="ingredient.name"
      :fallback-color="ingredient.color"
      size="lg"
    />
    <span class="font-pixel text-[8px] text-ramen-cream text-center leading-tight">
      {{ ingredient.name }}
    </span>
    <span class="text-[10px] text-ramen-cream/50 text-center">
      {{ ingredient.nameJp }}
    </span>
  </button>
</template>