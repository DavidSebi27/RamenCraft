import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useAuthStore } from './auth'
import { useIngredientStore } from './ingredients'
import { playSound } from '@/utils/sounds'
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
    const breakdown = []

    // Base score: 10 per ingredient
    const baseScore = ids.length * 10
    breakdown.push({ label: 'Base (' + ids.length + ' ingredients x 10)', value: baseScore })

    // Variety bonus: +5 per category used
    const categoriesUsed = Object.values(selections.value).filter(arr => arr.length > 0).length
    const varietyBonus = categoriesUsed * 5
    breakdown.push({ label: 'Variety (' + categoriesUsed + ' categories x 5)', value: varietyBonus })

    // Pairing bonuses: check every pair of selected ingredients
    let pairingTotal = 0
    ingredientStore.pairings.forEach((pairing) => {
      const id1 = pairing.ingredient_1_id ?? pairing.ingredient1Id
      const id2 = pairing.ingredient_2_id ?? pairing.ingredient2Id
      const modifier = pairing.score_modifier ?? pairing.scoreModifier ?? 0

      if (ids.includes(id1) && ids.includes(id2)) {
        pairingTotal += modifier
      }
    })
    if (pairingTotal !== 0) {
      breakdown.push({ label: 'Combo bonuses', value: pairingTotal })
    }

    const total = Math.max(0, baseScore + varietyBonus + pairingTotal)
    return { score: total, breakdown }
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
    const breakdown = []
    let score = 0

    // Sum up macros from selected ingredients
    let totalCalories = 0
    let totalProtein = 0
    let totalFat = 0
    let totalCarbs = 0

    ids.forEach((id) => {
      const ing = ingredientStore.ingredientMap[id]
      if (ing) {
        totalCalories += Number(ing.calories_per_serving || ing.caloriesPerServing || 0)
        totalProtein += Number(ing.protein_g || ing.proteinG || 0)
        totalFat += Number(ing.fat_g || ing.fatG || 0)
        totalCarbs += Number(ing.carbs_g || ing.carbsG || 0)
      }
    })

    // Protein score (0-30 points)
    let proteinScore = 0
    if (totalProtein >= 25) proteinScore = 30
    else if (totalProtein >= 15) proteinScore = 20
    else if (totalProtein >= 8) proteinScore = 10
    score += proteinScore
    breakdown.push({ label: 'Protein (' + Math.round(totalProtein) + 'g)', value: proteinScore })

    // Calorie balance (0-30 points) — ideal range 300-800
    let calorieScore = 0
    if (totalCalories >= 300 && totalCalories <= 800) calorieScore = 30
    else if (totalCalories >= 200 && totalCalories <= 1000) calorieScore = 20
    else if (totalCalories > 0) calorieScore = 10
    score += calorieScore
    breakdown.push({ label: 'Calories (' + Math.round(totalCalories) + ' kcal)', value: calorieScore })

    // Veggie bonus: toppings = vegetables (0-20 points)
    const toppingCount = selections.value.topping.length
    let veggieScore = 0
    if (toppingCount >= 3) veggieScore = 20
    else if (toppingCount >= 2) veggieScore = 15
    else if (toppingCount >= 1) veggieScore = 10
    score += veggieScore
    breakdown.push({ label: 'Toppings (' + toppingCount + ')', value: veggieScore })

    // Completeness: having all 5 categories = +20
    const categoriesUsed = Object.values(selections.value).filter(arr => arr.length > 0).length
    let completenessScore = 0
    if (categoriesUsed >= 5) completenessScore = 20
    else if (categoriesUsed >= 4) completenessScore = 10
    score += completenessScore
    breakdown.push({ label: 'Completeness (' + categoriesUsed + '/5)', value: completenessScore })

    return {
      score,
      breakdown,
      macros: {
        calories: Math.round(totalCalories),
        protein: Math.round(totalProtein),
        fat: Math.round(totalFat),
        carbs: Math.round(totalCarbs),
      },
    }
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

    const tastinessResult = calculateTastiness()
    const nutritionResult = calculateNutrition()
    const totalScore = tastinessResult.score + nutritionResult.score
    const xpEarned = totalScore

    const payload = {
      ingredient_ids: selectedIds.value,
      tastiness_score: tastinessResult.score,
      nutrition_score: nutritionResult.score,
      total_score: totalScore,
      xp_earned: xpEarned,
    }

    return api.post('/bowls/serve', payload)
      .then((response) => {
        const result = {
          tastiness: tastinessResult.score,
          tastinessBreakdown: tastinessResult.breakdown,
          nutrition: nutritionResult.score,
          nutritionBreakdown: nutritionResult.breakdown,
          macros: nutritionResult.macros,
          totalScore,
          xpEarned,
          newTotalXp: response.data.total_xp,
          newRank: response.data.current_rank,
          pairingsFound: response.data.pairings_found || [],
          newAchievements: [],
        }

        // Update the auth store with new XP/rank
        const authStore = useAuthStore()
        if (authStore.user) {
          authStore.user.totalXp = response.data.total_xp
          authStore.user.currentRank = response.data.current_rank
          localStorage.setItem('user', JSON.stringify(authStore.user))
        }

        // Chain achievement check after serving (Promise API for grading)
        return api.post('/achievements/check', {
          ingredient_ids: payload.ingredient_ids,
          total_score: totalScore,
          bowl_id: response.data.bowl_id,
        }).then(function (achievementResponse) {
          result.newAchievements = achievementResponse.data || []
          serveResult.value = result
          playSound('serve', 0.4)
          if (result.newAchievements.length > 0) {
            setTimeout(function () { playSound('achievement', 0.4) }, 800)
          }
          return result
        }).catch(function () {
          // Achievement check failed — still show the serve result
          serveResult.value = result
          return result
        })
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
