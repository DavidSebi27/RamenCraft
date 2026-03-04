<script setup>
import { computed } from 'vue'

/**
 * RankBadge — Displays the player's ramen rank
 *
 * Shows the rank name in romaji, kanji, and English translation.
 * Each rank has a unique color.
 *
 * Props:
 * - rank: One of 'minarai', 'jouren', 'tsuu', 'shokunin', 'taisho'
 */
const props = defineProps({
  rank: {
    type: String,
    default: 'minarai',
    validator: (v) => ['minarai', 'jouren', 'tsuu', 'shokunin', 'taisho'].includes(v),
  },
})

// Rank data lookup — maps rank key to display info
const rankData = {
  minarai:  { kanji: '見習い', english: 'Apprentice', color: '#9CA3AF' },
  jouren:   { kanji: '常連',   english: 'Regular',    color: '#60A5FA' },
  tsuu:     { kanji: '通',     english: 'Connoisseur', color: '#A78BFA' },
  shokunin: { kanji: '職人',   english: 'Craftsman',  color: '#F59E0B' },
  taisho:   { kanji: '大将',   english: 'Master',     color: '#EF4444' },
}

// Get the display data for the current rank
const info = computed(() => rankData[props.rank])
</script>

<template>
  <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-ramen-dark border border-ramen-brown rounded">
    <!-- Colored rank dot indicator -->
    <span class="w-3 h-3 rounded-sm" :style="{ backgroundColor: info.color }"></span>

    <div class="flex flex-col">
      <!-- Romaji + Kanji -->
      <span class="font-pixel text-[8px] uppercase" :style="{ color: info.color }">
        {{ rank }} {{ info.kanji }}
      </span>
      <!-- English translation -->
      <span class="text-[10px] text-ramen-cream/60">{{ info.english }}</span>
    </div>
  </div>
</template>