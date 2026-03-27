<script setup>
/**
 * ProfilePage — Player stats, rank, achievements, bowl history
 *
 * Reads real user data from the auth store and loads bowl history
 * from the bowl store on mount.
 */
import { onMounted, computed } from 'vue'
import NavBar from '@/components/organisms/NavBar/NavBar.vue'
import PlayerStats from '@/components/molecules/PlayerStats/PlayerStats.vue'
import AchievementCard from '@/components/molecules/AchievementCard/AchievementCard.vue'
import { useAuthStore } from '@/stores/auth'
import { useBowlStore } from '@/stores/bowl'
import { fetchAchievements } from '@/services/api'
import { ref } from 'vue'

const auth = useAuthStore()
const bowlStore = useBowlStore()

const achievements = ref([])
const userAchievements = ref([])

// Compute XP thresholds for the progress bar
const nextRank = computed(() => bowlStore.getNextRankThreshold(auth.user?.totalXp || 0))
const currentXp = computed(() => auth.user?.totalXp || 0)
const maxXp = computed(() => nextRank.value?.minXp || 10000)

onMounted(() => {
  // Load profile data and bowl history in parallel
  Promise.all([
    auth.fetchProfile(),
    bowlStore.fetchHistory(),
    fetchAchievements({ limit: 50 })
      .then((response) => {
        achievements.value = response.data || response
      })
      .catch(() => { /* achievements not critical */ }),
  ])
})
</script>

<template>
  <div class="min-h-screen bg-ramen-darker">
    <NavBar />
    <div class="max-w-4xl mx-auto p-4">
      <h1 class="font-pixel text-xl text-ramen-orange mb-6">Profile</h1>

      <!-- Player stats from auth store -->
      <PlayerStats
        :username="auth.username"
        :rank="auth.user?.currentRank || 'minarai'"
        :current-x-p="currentXp"
        :max-x-p="maxXp"
        :bowls-served="bowlStore.history.length"
      />

      <!-- Achievements section -->
      <h2 class="font-pixel text-sm text-ramen-cream mt-8 mb-4">Achievements</h2>
      <div v-if="achievements.length" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <AchievementCard
          v-for="achievement in achievements"
          :key="achievement.id || achievement.name"
          :achievement="achievement"
          :unlocked="userAchievements.some(ua => ua.achievement_id === achievement.id)"
        />
      </div>
      <p v-else class="text-sm text-ramen-cream/40">Loading achievements...</p>

      <!-- Bowl history -->
      <h2 class="font-pixel text-sm text-ramen-cream mt-8 mb-4">Bowl History</h2>
      <div v-if="bowlStore.historyLoading" class="text-sm text-ramen-cream/40">Loading...</div>
      <div v-else-if="bowlStore.history.length === 0" class="text-sm text-ramen-cream/40">
        No bowls served yet. Go build one!
      </div>
      <div v-else class="space-y-2">
        <div
          v-for="bowl in bowlStore.history"
          :key="bowl.id"
          class="bg-ramen-dark border border-ramen-brown p-3 flex justify-between items-center"
        >
          <div>
            <div class="font-pixel text-[8px] text-ramen-cream/40">
              {{ new Date(bowl.served_at).toLocaleDateString() }}
            </div>
            <div class="text-xs text-ramen-cream">
              {{ bowl.ingredients?.map(i => i.name).join(', ') || 'Bowl #' + bowl.id }}
            </div>
          </div>
          <div class="text-right">
            <div class="font-pixel text-xs text-ramen-gold">{{ bowl.total_score }} pts</div>
            <div class="font-pixel text-[8px] text-ramen-neon">+{{ bowl.xp_earned }} XP</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>