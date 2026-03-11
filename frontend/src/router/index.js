import { createRouter, createWebHistory } from 'vue-router'
import { isLoggedIn, getStoredUser } from '@/services/api'

// Import page components
// Each page is a top-level view that gets rendered by <router-view> in App.vue
import HomePage from '@/components/pages/HomePage.vue'
import PlayPage from '@/components/pages/PlayPage.vue'
import LeaderboardPage from '@/components/pages/LeaderboardPage.vue'
import ProfilePage from '@/components/pages/ProfilePage.vue'
import LoginPage from '@/components/pages/LoginPage.vue'
import RegisterPage from '@/components/pages/RegisterPage.vue'
import AdminDashboard from '@/components/pages/admin/AdminDashboard.vue'
import AdminIngredients from '@/components/pages/admin/AdminIngredients.vue'
import AdminPairings from '@/components/pages/admin/AdminPairings.vue'
import AdminAchievements from '@/components/pages/admin/AdminAchievements.vue'
import AdminUsers from '@/components/pages/admin/AdminUsers.vue'

// Define all application routes
// meta.requiresAuth: user must be logged in
// meta.requiresAdmin: user must have admin role
// meta.guestOnly: only accessible when NOT logged in (login/register)
const routes = [
  { path: '/', component: HomePage },
  { path: '/play', component: PlayPage, meta: { requiresAuth: true } },
  { path: '/leaderboard', component: LeaderboardPage },
  { path: '/profile', component: ProfilePage, meta: { requiresAuth: true } },
  { path: '/login', component: LoginPage, meta: { guestOnly: true } },
  { path: '/register', component: RegisterPage, meta: { guestOnly: true } },
  // Admin routes — require admin role
  { path: '/admin', component: AdminDashboard, meta: { requiresAuth: true, requiresAdmin: true } },
  { path: '/admin/ingredients', component: AdminIngredients, meta: { requiresAuth: true, requiresAdmin: true } },
  { path: '/admin/pairings', component: AdminPairings, meta: { requiresAuth: true, requiresAdmin: true } },
  { path: '/admin/achievements', component: AdminAchievements, meta: { requiresAuth: true, requiresAdmin: true } },
  { path: '/admin/users', component: AdminUsers, meta: { requiresAuth: true, requiresAdmin: true } },
]

// Create the router instance
// createWebHistory() uses the browser's History API for clean URLs (no # in the URL)
const router = createRouter({
  history: createWebHistory(),
  routes,
})

// Navigation guard — runs before every route change
router.beforeEach((to) => {
  const loggedIn = isLoggedIn()

  // Redirect guests away from protected routes
  if (to.meta.requiresAuth && !loggedIn) {
    return '/login'
  }

  // Redirect non-admins away from admin routes
  if (to.meta.requiresAdmin && loggedIn) {
    const user = getStoredUser()
    if (user?.role !== 'admin') {
      return '/play'
    }
  }

  // Redirect logged-in users away from login/register
  if (to.meta.guestOnly && loggedIn) {
    return '/play'
  }
})

export default router
