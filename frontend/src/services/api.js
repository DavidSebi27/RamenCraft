import axios from 'axios'

/**
 * Axios instance configured for the RamenCraft backend API.
 *
 * Base URL points to the Docker nginx container on port 8000.
 * A request interceptor automatically attaches the JWT token (if stored in localStorage).
 */
const api = axios.create({
  baseURL: 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
  },
})

// Attach JWT token to every request if the user is logged in
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// ============================================================
// Auth
// ============================================================

export async function login(email, password) {
  const { data } = await api.post('/auth/login', { email, password })
  localStorage.setItem('token', data.token)
  localStorage.setItem('user', JSON.stringify(data.user))
  return data
}

export async function register(username, email, password) {
  const { data } = await api.post('/auth/register', { username, email, password })
  localStorage.setItem('token', data.token)
  localStorage.setItem('user', JSON.stringify(data.user))
  return data
}

export async function fetchMe() {
  const { data } = await api.get('/auth/me')
  localStorage.setItem('user', JSON.stringify(data))
  return data
}

export function logout() {
  localStorage.removeItem('token')
  localStorage.removeItem('user')
}

export function getStoredUser() {
  const raw = localStorage.getItem('user')
  return raw ? JSON.parse(raw) : null
}

export function isLoggedIn() {
  return !!localStorage.getItem('token')
}

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

export async function createIngredient(body) {
  const { data } = await api.post('/ingredients', body)
  return data
}

export async function updateIngredient(id, body) {
  const { data } = await api.put(`/ingredients/${id}`, body)
  return data
}

export async function deleteIngredient(id) {
  const { data } = await api.delete(`/ingredients/${id}`)
  return data
}

// ============================================================
// Pairings
// ============================================================

export async function fetchPairings(params = {}) {
  const { data } = await api.get('/pairings', { params })
  return data
}

export async function createPairing(body) {
  const { data } = await api.post('/pairings', body)
  return data
}

export async function updatePairing(id, body) {
  const { data } = await api.put(`/pairings/${id}`, body)
  return data
}

export async function deletePairing(id) {
  const { data } = await api.delete(`/pairings/${id}`)
  return data
}

// ============================================================
// Achievements
// ============================================================

export async function fetchAchievements(params = {}) {
  const { data } = await api.get('/achievements', { params })
  return data
}

export async function createAchievement(body) {
  const { data } = await api.post('/achievements', body)
  return data
}

export async function updateAchievement(id, body) {
  const { data } = await api.put(`/achievements/${id}`, body)
  return data
}

export async function deleteAchievement(id) {
  const { data } = await api.delete(`/achievements/${id}`)
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

export async function updateUser(id, body) {
  const { data } = await api.put(`/users/${id}`, body)
  return data
}

export async function deleteUser(id) {
  const { data } = await api.delete(`/users/${id}`)
  return data
}

// ============================================================
// Bowls (serve + history)
// ============================================================

export function serveBowl(payload) {
  return api.post('/bowls/serve', payload)
}

export function fetchBowlHistory(params = {}) {
  return api.get('/bowls/history', { params })
}

// ============================================================
// Leaderboard
// ============================================================

export async function fetchLeaderboard(limit = 10) {
  const { data } = await api.get('/leaderboard', { params: { limit } })
  return data
}

export default api
