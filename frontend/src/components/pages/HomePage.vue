<script setup>
/**
 * HomePage — Landing page for RamenCraft
 *
 * Shows the game title, tagline, a Play button, and a preview of the rank tiers.
 * No NavBar here — this is the immersive landing screen.
 */
import PixelButton from '@/components/atoms/PixelButton/PixelButton.vue'
import { useRouter } from 'vue-router'

const router = useRouter()

// Rank tiers shown as a preview on the home page
const ranks = [
  { name: 'Minarai', kanji: '見習い', english: 'Apprentice', color: '#9CA3AF', xp: '0' },
  { name: 'Jouren', kanji: '常連', english: 'Regular', color: '#60A5FA', xp: '500' },
  { name: 'Tsuu', kanji: '通', english: 'Connoisseur', color: '#A78BFA', xp: '2,000' },
  { name: 'Shokunin', kanji: '職人', english: 'Craftsman', color: '#F59E0B', xp: '5,000' },
  { name: 'Taisho', kanji: '大将', english: 'Master', color: '#EF4444', xp: '15,000' },
]
</script>

<template>
  <div class="min-h-screen bg-ramen-darker flex flex-col">

    <!-- Hero section -->
    <div class="flex-1 flex flex-col items-center justify-center px-4 py-12">
      <!-- Title -->
      <h1 class="font-pixel text-3xl sm:text-4xl md:text-5xl text-ramen-orange mb-4 text-center">
        RamenCraft
      </h1>

      <!-- Tagline -->
      <p class="font-pixel text-xs sm:text-sm text-ramen-cream mb-8 text-center">
        Build. Serve. Rank Up.
      </p>

      <!-- Play button -->
      <PixelButton
        label="PLAY"
        variant="primary"
        size="lg"
        @click="router.push('/play')"
      />

      <!-- Sub-links -->
      <div class="flex gap-6 mt-6">
        <router-link to="/leaderboard" class="font-pixel text-[8px] text-ramen-cream/50 hover:text-ramen-neon transition-colors">
          Leaderboard
        </router-link>
        <router-link to="/login" class="font-pixel text-[8px] text-ramen-cream/50 hover:text-ramen-neon transition-colors">
          Login
        </router-link>
      </div>
    </div>

    <!-- Rank preview section -->
    <div class="px-4 pb-12">
      <h2 class="font-pixel text-xs text-ramen-cream/40 text-center mb-4">Ramen Ranks</h2>
      <div class="flex flex-wrap justify-center gap-3 max-w-2xl mx-auto">
        <div
          v-for="rank in ranks"
          :key="rank.name"
          class="bg-ramen-dark border border-ramen-brown px-3 py-2 text-center min-w-[120px]"
        >
          <div class="font-pixel text-[8px] uppercase" :style="{ color: rank.color }">
            {{ rank.name }}
          </div>
          <div class="text-sm" :style="{ color: rank.color }">{{ rank.kanji }}</div>
          <div class="text-[9px] text-ramen-cream/40">{{ rank.english }}</div>
          <div class="font-pixel text-[7px] text-ramen-gold mt-1">{{ rank.xp }} XP</div>
        </div>
      </div>
    </div>
  </div>
</template>