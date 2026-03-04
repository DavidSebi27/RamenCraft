<script setup>
import { computed } from 'vue'

/**
 * XPBar — Pixel art experience point progress bar
 *
 * Shows current XP as a filled bar and numeric label.
 *
 * Props:
 * - currentXP: Player's current XP within this rank tier
 * - maxXP: XP needed to reach the next rank
 */
const props = defineProps({
  currentXP: {
    type: Number,
    default: 0,
  },
  maxXP: {
    type: Number,
    default: 500,
  },
})

// Calculate fill percentage, capped at 100%
const fillPercent = computed(() => {
  if (props.maxXP <= 0) return 0
  return Math.min((props.currentXP / props.maxXP) * 100, 100)
})
</script>

<template>
  <div class="w-full">
    <!-- XP label -->
    <div class="flex justify-between mb-1">
      <span class="font-pixel text-[8px] text-ramen-gold">XP</span>
      <span class="font-pixel text-[8px] text-ramen-cream">
        {{ currentXP }} / {{ maxXP }}
      </span>
    </div>

    <!-- Progress bar track -->
    <div class="w-full h-4 bg-ramen-dark border-2 border-ramen-brown">
      <!-- Filled portion -->
      <div
        class="h-full bg-ramen-gold transition-all duration-500"
        :style="{ width: fillPercent + '%' }"
      ></div>
    </div>
  </div>
</template>