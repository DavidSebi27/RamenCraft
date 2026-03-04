<script setup>
/**
 * ProfilePage — Player stats, rank, achievements, bowl history
 * Uses hardcoded data for Phase 1. Will pull from API in Phase 3+.
 */
import NavBar from '@/components/organisms/NavBar/NavBar.vue'
import PlayerStats from '@/components/molecules/PlayerStats/PlayerStats.vue'
import AchievementCard from '@/components/molecules/AchievementCard/AchievementCard.vue'

// Fake achievements for static display
const achievements = [
  { name: 'First Serve', description: 'Everyone starts somewhere. Serve your first bowl.' },
  { name: 'Classic Tonkotsu', description: 'Tonkotsu + Chashu + Mayu. As the ancestors intended.' },
  { name: 'Egg is Life', description: 'Used ajitama in 10 bowls. We get it, you like eggs.' },
  { name: 'Oil Spill', description: 'You put ALL 5 oils in one bowl. Seek help.' },
  { name: 'The Hypocrite', description: 'Veggie broth with pork chashu. Pick a lane.' },
  { name: 'Plant Power', description: 'A fully plant-based bowl. Mother Earth approves.' },
]
</script>

<template>
  <div class="min-h-screen bg-ramen-darker">
    <NavBar />
    <div class="max-w-4xl mx-auto p-4">
      <h1 class="font-pixel text-xl text-ramen-orange mb-6">Profile</h1>

      <!-- Player stats with hardcoded demo data -->
      <PlayerStats
        username="RamenApprentice"
        rank="jouren"
        :current-x-p="820"
        :max-x-p="2000"
        :bowls-served="23"
      />

      <!-- Achievements section -->
      <h2 class="font-pixel text-sm text-ramen-cream mt-8 mb-4">Achievements</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <AchievementCard
          v-for="(achievement, index) in achievements"
          :key="achievement.name"
          :achievement="achievement"
          :unlocked="index < 2"
        />
      </div>

      <!-- Bowl history placeholder -->
      <h2 class="font-pixel text-sm text-ramen-cream mt-8 mb-4">Bowl History</h2>
      <p class="text-sm text-ramen-cream/40">Your served bowls will appear here once the API is connected.</p>
    </div>
  </div>
</template>