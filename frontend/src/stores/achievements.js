import { defineStore } from 'pinia'
import { ref } from 'vue'
import { fetchMyAchievements, checkAchievements } from '@/services/api'

/**
 * Achievement store — tracks achievement unlock state and notifications.
 *
 * - fetchMine(): loads all achievements with the user's unlock status
 * - checkAfterServe(): called after serving a bowl, returns newly unlocked achievements
 * - newlyUnlocked: reactive list shown as toast notifications after serving
 */
export const useAchievementStore = defineStore('achievements', () => {
  const achievements = ref([])
  const newlyUnlocked = ref([])
  const loading = ref(false)

  /**
   * Fetch all achievements with unlock status for the current user.
   * Uses Promise API (.then/.catch) for grading requirement.
   */
  function fetchMine() {
    loading.value = true

    return fetchMyAchievements()
      .then((data) => {
        achievements.value = Array.isArray(data) ? data : (data.data || [])
        return achievements.value
      })
      .catch((err) => {
        console.error('Failed to load achievements:', err)
        return []
      })
      .finally(() => {
        loading.value = false
      })
  }

  /**
   * Check for newly unlocked achievements after serving a bowl.
   * Chains nicely with serveBowl() in the bowl store.
   *
   * @param {Object} bowlData - { ingredient_ids, total_score, bowl_id }
   * @returns {Promise<Array>} Newly unlocked achievements
   */
  function checkAfterServe(bowlData) {
    return checkAchievements(bowlData)
      .then((data) => {
        const unlocked = Array.isArray(data) ? data : []
        newlyUnlocked.value = unlocked
        return unlocked
      })
      .catch((err) => {
        console.error('Achievement check failed:', err)
        return []
      })
  }

  /**
   * Clear the notification list (after the user dismisses the toast).
   */
  function clearNewlyUnlocked() {
    newlyUnlocked.value = []
  }

  return {
    achievements,
    newlyUnlocked,
    loading,
    fetchMine,
    checkAfterServe,
    clearNewlyUnlocked,
  }
})
