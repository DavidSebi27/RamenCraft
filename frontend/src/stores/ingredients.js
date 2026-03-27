import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { fetchCategories, fetchIngredientsByCategory, fetchPairings } from '@/services/api'
import { enrichWithColors } from '@/data/ingredients'

/**
 * Ingredient store — manages categories, ingredients, and pairings.
 *
 * Demonstrates the "query client" pattern: each async operation tracks
 * its own loading/error/data state so components can show proper UI feedback.
 */
export const useIngredientStore = defineStore('ingredients', () => {
  // State — query client pattern (data + loading + error for each resource)
  const categories = ref([])
  const ingredientsByCategory = ref({})
  const ingredientMap = ref({})
  const pairings = ref([])

  const loading = ref(false)
  const error = ref(null)
  const loaded = ref(false)

  // Getters
  const allIngredients = computed(() => Object.values(ingredientMap.value))
  const categoryNames = computed(() => categories.value.map(c => c.name))

  /**
   * Load all categories + ingredients in parallel using Promise.all.
   * This is the main "query" — called once when the PlayPage mounts.
   * Skips re-fetch if data is already loaded (simple cache).
   */
  function loadAll(forceRefresh = false) {
    if (loaded.value && !forceRefresh) return Promise.resolve()

    loading.value = true
    error.value = null

    // Step 1: fetch categories, then fetch ingredients per category in parallel
    return fetchCategories()
      .then((cats) => {
        categories.value = cats.sort((a, b) => a.sortOrder - b.sortOrder)

        // Step 2: Promise.all — fetch all category ingredients in parallel
        const ingredientPromises = cats.map((cat) =>
          fetchIngredientsByCategory(cat.name)
            .then((raw) => ({
              name: cat.name,
              ingredients: enrichWithColors(raw),
            }))
        )

        // Also fetch pairings in parallel with ingredients
        const pairingPromise = fetchPairings()
          .then((response) => response.data || response)

        return Promise.all([Promise.all(ingredientPromises), pairingPromise])
      })
      .then(([ingredientResults, pairingData]) => {
        // Build lookup maps
        const byCategory = {}
        const byId = {}
        ingredientResults.forEach(({ name, ingredients }) => {
          byCategory[name] = ingredients
          ingredients.forEach((ing) => { byId[ing.id] = ing })
        })

        ingredientsByCategory.value = byCategory
        ingredientMap.value = byId
        pairings.value = Array.isArray(pairingData) ? pairingData : []
        loaded.value = true
      })
      .catch((err) => {
        console.error('Failed to load ingredients:', err)
        error.value = 'Failed to load ingredients. Is the backend running?'
        throw err
      })
      .finally(() => {
        loading.value = false
      })
  }

  return {
    // State
    categories,
    ingredientsByCategory,
    ingredientMap,
    pairings,
    loading,
    error,
    loaded,
    // Getters
    allIngredients,
    categoryNames,
    // Actions
    loadAll,
  }
})
