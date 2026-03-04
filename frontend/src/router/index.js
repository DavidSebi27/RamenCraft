import { createRouter, createWebHistory } from 'vue-router'

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
// Each route maps a URL path to a page component
const routes = [
  { path: '/', component: HomePage },
  { path: '/play', component: PlayPage },
  { path: '/leaderboard', component: LeaderboardPage },
  { path: '/profile', component: ProfilePage },
  { path: '/login', component: LoginPage },
  { path: '/register', component: RegisterPage },
  // Admin routes — will be protected with auth guards in Phase 4
  { path: '/admin', component: AdminDashboard },
  { path: '/admin/ingredients', component: AdminIngredients },
  { path: '/admin/pairings', component: AdminPairings },
  { path: '/admin/achievements', component: AdminAchievements },
  { path: '/admin/users', component: AdminUsers },
]

// Create the router instance
// createWebHistory() uses the browser's History API for clean URLs (no # in the URL)
const router = createRouter({
  history: createWebHistory(),
  routes,
})

export default router
