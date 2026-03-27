import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useAuthStore } from './auth'
import { useIngredientStore } from './ingredients'
import api from '@/services/api'

/**
 * Bowl store — manages the current bowl being built and game logic.
 *
 * Handles: ingredient selection, score calculation (tastiness + nutrition),
 * serve action (POST to backend), XP earning, and rank progression.
 *
 * Rank thresholds (cumulative XP):
 *   minarai   → 0 XP      (Apprentice — just starting out)
 *   jouren    → 500 XP    (Regular — knows the basics)
 *   tsuu      → 2000 XP   (Connoisseur — refined palate)
 *   shokunin  → 5000 XP   (Craftsman — serious skill)
 *   taisho    → 10000 XP  (Master — ramen legend)
 */

const RANK_THRESHOLDS = [
  { rank: 'minarai',  minXp: 0 },
  { rank: 'jouren',   minXp: 500 },
  { rank: 'tsuu',     minXp: 2000 },
  { rank: 'shokunin', minXp: 5000 },
  { rank: 'taisho',   minXp: 10000 },
]

export const useBowlStore = defineStore('bowl', () => {
  // Current bowl selections — keyed by category name, value is array of ingredient IDs
  const selections = ref({
    broth: [],
    noodles: [],
    oil: [],
    protein: [],
    topping: [],
  })

  // Serve result (shown after serving)
  const serveResult = ref(null)
  const serving = ref(false)
  const serveError = ref(null)

  // Bowl history for the current session
  const history = ref([])
  const historyLoading = ref(false)

  // Getters
  const totalIngredients = computed(() =>
    Object.values(selections.value).flat().length
  )

  const isEmpty = computed(() => totalIngredients.value === 0)

  const hasMinimumBowl = computed(() => {
    // A valid bowl needs at least a broth and noodles
    return selections.value.broth.length > 0 && selections.value.noodles.length > 0
  })

  /**
   * Get all selected ingredient IDs as a flat array.
   */
  const selectedIds = computed(() =>
    Object.values(selections.value).flat()
  )

  // Actions

  function updateSelections(newSelections) {
    selections.value = newSelections
    // Clear previous serve result when bowl changes
    serveResult.value = null
    serveError.value = null
  }

  function resetBowl() {
    selections.value = {
      broth: [],
      noodles: [],
      oil: [],
      protein: [],
      topping: [],
    }
    serveResult.value = null
    serveError.value = null
  }

  /**
   * Calculate tastiness score based on ingredient pairings.
   *
   * Base score: 10 points per ingredient selected.
   * Pairing bonuses: checked against the pairings table.
   * Variety bonus: +5 per category that has at least one ingredient.
   */
  function calculateTastiness() {
    const ingredientStore = useIngredientStore()
    const ids = selectedIds.value

    // Base score: 10 per ingredient
    let score = ids.length * 10

    // Variety bonus: +5 per category used
    const categoriesUsed = Object.values(selections.value).filter(arr => arr.length > 0).length
    score += categoriesUsed * 5

    // Pairing bonuses: check every pair of selected ingredients
    ingredientStore.pairings.forEach((pairing) => {
      const id1 = pairing.ingredient_1_id ?? pairing.ingredient1Id
      const id2 = pairing.ingredient_2_id ?? pairing.ingredient2Id
      const modifier = pairing.score_modifier ?? pairing.scoreModifier ?? 0

      if (ids.includes(id1) && ids.includes(id2)) {
        score += modifier
      }
    })

    return Math.max(0, score)
  }

  /**
   * Calculate nutrition score based on macronutrient balance.
   *
   * A balanced bowl scores higher. We check:
   * - Protein adequacy (>= 15g is good)
   * - Calorie range (300-800 cal is ideal)
   * - Has vegetables (toppings count)
   */
  function calculateNutrition() {
    const ingredientStore = useIngredientStore()
    const ids = selectedIds.value
    let score = 0

    // Sum up macros from selected ingredients
    let totalCalories = 0
    let totalProtein = 0

    ids.forEach((id) => {
      const ing = ingredientStore.ingredientMap[id]
      if (ing) {
        totalCalories += Number(ing.calories_per_serving || ing.caloriesPerServing || 0)
        totalProtein += Number(ing.protein_g || ing.proteinG || 0)
      }
    })

    // Protein score (0-30 points)
    if (totalProtein >= 25) score += 30
    else if (totalProtein >= 15) score += 20
    else if (totalProtein >= 8) score += 10

    // Calorie balance (0-30 points) — ideal range 300-800
    if (totalCalories >= 300 && totalCalories <= 800) score += 30
    else if (totalCalories >= 200 && totalCalories <= 1000) score += 20
    else if (totalCalories > 0) score += 10

    // Veggie bonus: toppings = vegetables (0-20 points)
    const toppingCount = selections.value.topping.length
    if (toppingCount >= 3) score += 20
    else if (toppingCount >= 2) score += 15
    else if (toppingCount >= 1) score += 10

    // Completeness: having all 5 categories = +20
    const categoriesUsed = Object.values(selections.value).filter(arr => arr.length > 0).length
    if (categoriesUsed >= 5) score += 20
    else if (categoriesUsed >= 4) score += 10

    return score
  }

  /**
   * Serve the bowl — calculate scores and POST to the backend.
   * Returns a Promise with the serve result.
   */
  function serveBowl() {
    if (!hasMinimumBowl.value) {
      serveError.value = 'You need at least a broth and noodles!'
      return Promise.reject(new Error(serveError.value))
    }

    serving.value = true
    serveError.value = null

    const tastiness = calculateTastiness()
    const nutrition = calculateNutrition()
    const totalScore = tastiness + nutrition
    const xpEarned = totalScore

    const payload = {
      ingredient_ids: selectedIds.value,
      tastiness_score: tastiness,
      nutrition_score: nutrition,
      total_score: totalScore,
      xp_earned: xpEarned,
    }

    return api.post('/bowls/serve', payload)
      .then((response) => {
        const result = {
          tastiness,
          nutrition,
          totalScore,
          xpEarned,
          newTotalXp: response.data.total_xp,
          newRank: response.data.current_rank,
          pairingsFound: response.data.pairings_found || [],
        }
        serveResult.value = result

        // Update the auth store with new XP/rank
        // Auth endpoint returns camelCase (totalXp, currentRank)
        const authStore = useAuthStore()
        if (authStore.user) {
          authStore.user.totalXp = response.data.total_xp
          authStore.user.currentRank = response.data.current_rank
          // Sync to localStorage so XP persists across page refresh
          localStorage.setItem('user', JSON.stringify(authStore.user))
        }

        return result
      })
      .catch((err) => {
        serveError.value = err.response?.data?.error || 'Failed to serve bowl'
        throw err
      })
      .finally(() => {
        serving.value = false
      })
  }

  /**
   * Fetch the player's bowl history.
   */
  function fetchHistory() {
    historyLoading.value = true

    return api.get('/bowls/history')
      .then((response) => {
        history.value = response.data.data || response.data
        return history.value
      })
      .catch((err) => {
        console.error('Failed to load bowl history:', err)
        return []
      })
      .finally(() => {
        historyLoading.value = false
      })
  }

  /**
   * Get the rank name for a given XP total.
   */
  function getRankForXp(xp) {
    let rank = RANK_THRESHOLDS[0].rank
    for (const threshold of RANK_THRESHOLDS) {
      if (xp >= threshold.minXp) rank = threshold.rank
    }
    return rank
  }

  /**
   * Get XP needed for next rank.
   */
  function getNextRankThreshold(currentXp) {
    for (const threshold of RANK_THRESHOLDS) {
      if (currentXp < threshold.minXp) return threshold
    }
    return null // Already max rank
  }

  return {
    // State
    selections,
    serveResult,
    serving,
    serveError,
    history,
    historyLoading,
    // Getters
    totalIngredients,
    isEmpty,
    hasMinimumBowl,
    selectedIds,
    // Actions
    updateSelections,
    resetBowl,
    calculateTastiness,
    calculateNutrition,
    serveBowl,
    fetchHistory,
    getRankForXp,
    getNextRankThreshold,
    // Constants (exposed for components)
    RANK_THRESHOLDS,
  }
})
