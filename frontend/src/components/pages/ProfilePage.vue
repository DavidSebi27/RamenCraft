<script setup>
/**
 * ProfilePage — Player stats, rank, achievements, bowl history
 *
 * Reads real user data from the auth store and loads bowl history
 * from the bowl store on mount.
 */
import { onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import NavBar from '@/components/organisms/NavBar/NavBar.vue'
import PlayerStats from '@/components/molecules/PlayerStats/PlayerStats.vue'
import AchievementCard from '@/components/molecules/AchievementCard/AchievementCard.vue'
import PixelButton from '@/components/atoms/PixelButton/PixelButton.vue'
import { useAuthStore } from '@/stores/auth'
import { useBowlStore } from '@/stores/bowl'
import { useAchievementStore } from '@/stores/achievements'
import { useFavoritesStore } from '@/stores/favorites'

const router = useRouter()
const auth = useAuthStore()
const bowlStore = useBowlStore()
const achievementStore = useAchievementStore()
const favoritesStore = useFavoritesStore()

// Compute XP thresholds for the progress bar
const nextRank = computed(() => bowlStore.getNextRankThreshold(auth.user?.totalXp || 0))
const currentXp = computed(() => auth.user?.totalXp || 0)
const maxXp = computed(() => nextRank.value?.minXp || 10000)

/**
 * Load a favorite bowl into the bowl store and navigate to Play.
 * Groups ingredient IDs by category to match the selections format.
 */
function loadFavorite(favorite) {
  const selections = { broth: [], noodles: [], oil: [], protein: [], topping: [] }
  favorite.ingredients.forEach((ing) => {
    const cat = ing.category
    if (selections[cat]) {
      selections[cat].push(ing.id)
    }
  })
  bowlStore.updateSelections(selections)
  router.push('/play')
}

function removeFavorite(id) {
  favoritesStore.remove(id)
}

onMounted(() => {
  // Load profile data, bowl history, and achievements in parallel (Promise.all)
  Promise.all([
    auth.fetchProfile(),
    bowlStore.fetchHistory(),
    achievementStore.fetchMine(),
    favoritesStore.fetchAll(),
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
        :bowls-served="auth.user?.bowlsServed || 0"
      />

      <!-- Achievements section -->
      <h2 class="font-pixel text-sm text-ramen-cream mt-8 mb-4">
        Achievements
        <span class="text-ramen-cream/40 text-[8px]">
          ({{ achievementStore.achievements.filter(a => a.unlocked).length }}/{{ achievementStore.achievements.length }})
        </span>
      </h2>
      <div v-if="achievementStore.loading" class="text-sm text-ramen-cream/40">Loading...</div>
      <div v-else-if="achievementStore.achievements.length" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <AchievementCard
          v-for="achievement in achievementStore.achievements"
          :key="achievement.id"
          :achievement="achievement"
          :unlocked="achievement.unlocked"
        />
      </div>
      <p v-else class="text-sm text-ramen-cream/40">No achievements found.</p>

      <!-- Favorites section -->
      <h2 class="font-pixel text-sm text-ramen-cream mt-8 mb-4">
        Saved Bowls
        <span class="text-ramen-cream/40 text-[8px]">({{ favoritesStore.favorites.length }})</span>
      </h2>
      <div v-if="favoritesStore.loading" class="text-sm text-ramen-cream/40">Loading...</div>
      <div v-else-if="favoritesStore.favorites.length === 0" class="text-sm text-ramen-cream/40">
        No saved bowls yet. Serve a bowl and save it!
      </div>
      <div v-else class="space-y-2">
        <div
          v-for="fav in favoritesStore.favorites"
          :key="fav.id"
          class="bg-ramen-dark border border-ramen-brown p-3 flex justify-between items-center"
        >
          <div>
            <div class="font-pixel text-[10px] text-ramen-gold">{{ fav.name }}</div>
            <div class="text-[10px] text-ramen-cream/50 mt-0.5">
              {{ fav.ingredients.map(i => i.name).join(', ') }}
            </div>
          </div>
          <div class="flex gap-2">
            <PixelButton label="LOAD" variant="primary" size="sm" @click="loadFavorite(fav)" />
            <PixelButton label="X" variant="danger" size="sm" @click="removeFavorite(fav.id)" />
          </div>
        </div>
      </div>

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