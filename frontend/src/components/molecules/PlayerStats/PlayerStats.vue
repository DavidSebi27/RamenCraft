<script setup>
import RankBadge from '@/components/atoms/RankBadge/RankBadge.vue'
import XPBar from '@/components/atoms/XPBar/XPBar.vue'

/**
 * PlayerStats — Combines rank badge, XP bar, and basic stats
 *
 * Props:
 * - username: Player's display name
 * - rank: Current rank key (e.g., 'minarai')
 * - currentXP: XP within current rank tier
 * - maxXP: XP needed for next rank
 * - bowlsServed: Total bowls served
 */
defineProps({
  username: {
    type: String,
    default: 'Player',
  },
  rank: {
    type: String,
    default: 'minarai',
  },
  currentXP: {
    type: Number,
    default: 0,
  },
  maxXP: {
    type: Number,
    default: 500,
  },
  bowlsServed: {
    type: Number,
    default: 0,
  },
})
</script>

<template>
  <div class="bg-ramen-dark border border-ramen-brown p-4 space-y-3">
    <!-- Username -->
    <h2 class="font-pixel text-sm text-ramen-cream">{{ username }}</h2>

    <!-- Rank badge -->
    <RankBadge :rank="rank" />

    <!-- XP progress bar -->
    <XPBar :current-x-p="currentXP" :max-x-p="maxXP" />

    <!-- Stats row -->
    <div class="flex gap-6 text-sm text-ramen-cream/60">
      <div>
        <span class="font-pixel text-[8px] text-ramen-cream/40">Bowls Served</span>
        <div class="font-pixel text-xs text-ramen-cream">{{ bowlsServed }}</div>
      </div>
      <div>
        <span class="font-pixel text-[8px] text-ramen-cream/40">Total XP</span>
        <div class="font-pixel text-xs text-ramen-gold">{{ currentXP }}</div>
      </div>
    </div>
  </div>
</template>