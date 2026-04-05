import { defineStore } from 'pinia'
import { ref } from 'vue'
import { fetchFavorites, saveFavorite, deleteFavorite } from '@/services/api'

/**
 * Favorites store — save and load favorite bowl configurations.
 *
 * Players can save their current bowl selections as a named favorite,
 * then reload them later to quickly rebuild the same bowl.
 */
export const useFavoritesStore = defineStore('favorites', () => {
  const favorites = ref([])
  const loading = ref(false)
  const error = ref(null)

  /**
   * Fetch all favorites for the current user.
   */
  function fetchAll() {
    loading.value = true
    error.value = null

    return fetchFavorites({ limit: 50 })
      .then((data) => {
        favorites.value = data.data || []
        return favorites.value
      })
      .catch((err) => {
        error.value = 'Failed to load favorites'
        console.error(err)
        return []
      })
      .finally(() => {
        loading.value = false
      })
  }

  /**
   * Save the current bowl as a favorite.
   * @param {string} name - Name for the saved bowl
   * @param {number[]} ingredientIds - Array of ingredient IDs
   */
  function save(name, ingredientIds) {
    return saveFavorite(name, ingredientIds)
      .then((data) => {
        // Refresh the list after saving
        return fetchAll().then(() => data)
      })
      .catch((err) => {
        error.value = err.response?.data?.error || 'Failed to save favorite'
        throw err
      })
  }

  /**
   * Delete a saved favorite.
   * @param {number} id - Favorite ID
   */
  function remove(id) {
    return deleteFavorite(id)
      .then(() => {
        favorites.value = favorites.value.filter((f) => f.id !== id)
      })
      .catch((err) => {
        error.value = err.response?.data?.error || 'Failed to delete favorite'
        throw err
      })
  }

  return {
    favorites,
    loading,
    error,
    fetchAll,
    save,
    remove,
  }
})
