<script setup>
/**
 * LeaderboardTable — Fetches and displays top players from the API
 */
import { ref, onMounted } from 'vue'
import { fetchLeaderboard } from '@/services/api'

const players = ref([])
const loading = ref(true)
const error = ref(null)

// Color for each rank tier
const tierColors = {
  minarai: '#9CA3AF',
  jouren: '#60A5FA',
  tsuu: '#A78BFA',
  shokunin: '#F59E0B',
  taisho: '#EF4444',
}

onMounted(() => {
  fetchLeaderboard(20)
    .then((data) => {
      players.value = Array.isArray(data) ? data : (data.data || [])
    })
    .catch((err) => {
      error.value = 'Failed to load leaderboard'
      console.error(err)
    })
    .finally(() => {
      loading.value = false
    })
})
</script>

<template>
  <div>
    <div v-if="loading" class="font-pixel text-[10px] text-ramen-cream/50 text-center py-8">
      Loading...
    </div>
    <div v-else-if="error" class="font-pixel text-[10px] text-ramen-red text-center py-8">
      {{ error }}
    </div>
    <div v-else-if="players.length === 0" class="font-pixel text-[10px] text-ramen-cream/40 text-center py-8">
      No players yet. Be the first!
    </div>
    <div v-else class="overflow-x-auto">
      <table class="w-full text-left">
        <thead>
          <tr class="border-b border-ramen-brown">
            <th class="font-pixel text-[8px] text-ramen-cream/40 pb-2 pr-4">#</th>
            <th class="font-pixel text-[8px] text-ramen-cream/40 pb-2 pr-4">Player</th>
            <th class="font-pixel text-[8px] text-ramen-cream/40 pb-2 pr-4">Rank</th>
            <th class="font-pixel text-[8px] text-ramen-cream/40 pb-2">XP</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="player in players"
            :key="player.id"
            class="border-b border-ramen-brown/30"
          >
            <!-- Rank number -->
            <td class="py-2 pr-4">
              <span
                class="font-pixel text-sm"
                :class="player.rank <= 3 ? 'text-ramen-gold' : 'text-ramen-cream/60'"
              >
                {{ player.rank }}
              </span>
            </td>

            <!-- Player name + tier indicator -->
            <td class="py-2 pr-4">
              <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-sm" :style="{ backgroundColor: tierColors[player.currentRank] }"></span>
                <span class="text-sm text-ramen-cream">{{ player.username }}</span>
              </div>
            </td>

            <!-- Rank title -->
            <td class="py-2 pr-4">
              <span
                class="font-pixel text-[8px] uppercase"
                :style="{ color: tierColors[player.currentRank] }"
              >
                {{ player.currentRank }}
              </span>
            </td>

            <!-- XP -->
            <td class="py-2">
              <span class="font-pixel text-[10px] text-ramen-gold">{{ player.totalXp.toLocaleString() }}</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
