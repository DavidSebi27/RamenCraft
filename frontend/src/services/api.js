import axios from 'axios'

/**
 * Axios instance configured for the RamenCraft backend API.
 *
 * Base URL points to the Docker nginx container on port 8000.
 * In Phase 4 (auth), we'll add an interceptor to attach JWT tokens.
 */
const api = axios.create({
  baseURL: 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
  },
})

// ============================================================
// Categories
// ============================================================

export async function fetchCategories() {
  const { data } = await api.get('/categories')
  return data
}

// ============================================================
// Ingredients
// ============================================================

export async function fetchIngredients(params = {}) {
  const { data } = await api.get('/ingredients', { params })
  return data
}

export async function fetchIngredientsByCategory(categoryName) {
  const { data } = await api.get('/ingredients', {
    params: { category: categoryName, limit: 50 },
  })
  return data.data
}

export async function fetchIngredientById(id) {
  const { data } = await api.get(`/ingredients/${id}`)
  return data
}

// ============================================================
// Pairings
// ============================================================

export async function fetchPairings(params = {}) {
  const { data } = await api.get('/pairings', { params })
  return data
}

// ============================================================
// Achievements
// ============================================================

export async function fetchAchievements(params = {}) {
  const { data } = await api.get('/achievements', { params })
  return data
}

// ============================================================
// Users
// ============================================================

export async function fetchUsers(params = {}) {
  const { data } = await api.get('/users', { params })
  return data
}

export async function fetchUserById(id) {
  const { data } = await api.get(`/users/${id}`)
  return data
}

// ============================================================
// Leaderboard
// ============================================================

export async function fetchLeaderboard(limit = 10) {
  const { data } = await api.get('/leaderboard', { params: { limit } })
  return data
}

export default api
