<script setup>
import { ref } from 'vue'

/**
 * IngredientIcon — Displays a pixel art sprite for an ingredient
 *
 * If the sprite file doesn't exist yet (404), it shows a colored placeholder
 * circle with the first letter of the ingredient name.
 *
 * Props:
 * - src: Path to the sprite image (e.g., '/sprites/broth-tonkotsu-icon.png')
 * - alt: Alt text for the image
 * - size: 'sm' (32px), 'md' (48px), 'lg' (64px)
 * - fallbackColor: Hex color used for the placeholder when sprite is missing
 */
defineProps({
  src: {
    type: String,
    required: true,
  },
  alt: {
    type: String,
    default: 'Ingredient',
  },
  size: {
    type: String,
    default: 'md',
    validator: (v) => ['sm', 'md', 'lg'].includes(v),
  },
  fallbackColor: {
    type: String,
    default: '#888888',
  },
})

// Track whether the sprite image failed to load
const imgError = ref(false)
</script>

<template>
  <div
    class="relative flex items-center justify-center"
    :class="[
      size === 'sm' ? 'w-8 h-8' : '',
      size === 'md' ? 'w-12 h-12' : '',
      size === 'lg' ? 'w-16 h-16' : '',
    ]"
  >
    <!-- Actual sprite image (hidden if it fails to load) -->
    <img
      v-if="!imgError"
      :src="src"
      :alt="alt"
      class="w-full h-full pixel-render object-contain"
      @error="imgError = true"
    />

    <!-- Fallback: colored circle with first letter -->
    <div
      v-else
      class="w-full h-full rounded-lg flex items-center justify-center font-pixel text-white"
      :class="[
        size === 'sm' ? 'text-[8px]' : '',
        size === 'md' ? 'text-[10px]' : '',
        size === 'lg' ? 'text-xs' : '',
      ]"
      :style="{ backgroundColor: fallbackColor }"
    >
      {{ alt.charAt(0).toUpperCase() }}
    </div>
  </div>
</template>