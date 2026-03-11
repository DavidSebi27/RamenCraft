<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { getStoredUser, isLoggedIn, logout } from '@/services/api'

const router = useRouter()

// Controls whether the mobile hamburger menu is open or closed
const mobileMenuOpen = ref(false)

// Current user (null if not logged in)
const user = ref(null)

onMounted(() => {
  if (isLoggedIn()) {
    user.value = getStoredUser()
  }
})

// Re-check user on each navigation (in case login/logout happened)
router.afterEach(() => {
  user.value = isLoggedIn() ? getStoredUser() : null
})

function handleLogout() {
  logout()
  user.value = null
  mobileMenuOpen.value = false
  router.push('/')
}

// Navigation links used in both desktop and mobile menus
const navLinks = [
  { name: 'Home', path: '/' },
  { name: 'Play', path: '/play' },
  { name: 'Leaderboard', path: '/leaderboard' },
  { name: 'Profile', path: '/profile' },
]
</script>

<template>
  <nav class="bg-ramen-dark border-b-4 border-ramen-brown">
    <div class="max-w-6xl mx-auto px-4">
      <div class="flex justify-between items-center h-16">

        <!-- Logo / Brand -->
        <router-link to="/" class="font-pixel text-sm text-ramen-orange hover:text-ramen-gold transition-colors">
          RamenCraft
        </router-link>

        <!-- Desktop nav links (hidden on mobile) -->
        <div class="hidden md:flex items-center gap-6">
          <router-link
            v-for="link in navLinks"
            :key="link.path"
            :to="link.path"
            class="font-pixel text-xs text-ramen-cream hover:text-ramen-orange transition-colors"
          >
            {{ link.name }}
          </router-link>

          <!-- Admin link (only for admins) -->
          <router-link
            v-if="user?.role === 'admin'"
            to="/admin"
            class="font-pixel text-xs text-ramen-neon hover:text-ramen-orange transition-colors"
          >
            Admin
          </router-link>

          <!-- Logged in: show username + logout -->
          <template v-if="user">
            <span class="font-pixel text-xs text-ramen-gold">{{ user.username }}</span>
            <button
              @click="handleLogout"
              class="font-pixel text-xs bg-ramen-brown text-ramen-cream px-3 py-2 hover:bg-ramen-red transition-colors"
            >
              Logout
            </button>
          </template>

          <!-- Not logged in: show login button -->
          <router-link
            v-else
            to="/login"
            class="font-pixel text-xs bg-ramen-red text-ramen-cream px-3 py-2 hover:bg-ramen-orange transition-colors"
          >
            Login
          </router-link>
        </div>

        <!-- Mobile hamburger button (hidden on desktop) -->
        <button
          class="md:hidden text-ramen-cream hover:text-ramen-orange transition-colors"
          aria-label="Toggle menu"
          @click="mobileMenuOpen = !mobileMenuOpen"
        >
          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path
              v-if="!mobileMenuOpen"
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 6h16M4 12h16M4 18h16"
            />
            <path
              v-else
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>
      </div>

      <!-- Mobile menu dropdown (only visible when toggled) -->
      <div v-if="mobileMenuOpen" class="md:hidden pb-4 flex flex-col gap-2">
        <router-link
          v-for="link in navLinks"
          :key="link.path"
          :to="link.path"
          class="font-pixel text-xs text-ramen-cream hover:text-ramen-orange transition-colors py-2"
          @click="mobileMenuOpen = false"
        >
          {{ link.name }}
        </router-link>

        <!-- Admin link (mobile, only for admins) -->
        <router-link
          v-if="user?.role === 'admin'"
          to="/admin"
          class="font-pixel text-xs text-ramen-neon hover:text-ramen-orange transition-colors py-2"
          @click="mobileMenuOpen = false"
        >
          Admin
        </router-link>

        <!-- Logged in: username + logout (mobile) -->
        <template v-if="user">
          <span class="font-pixel text-xs text-ramen-gold py-2">{{ user.username }}</span>
          <button
            @click="handleLogout"
            class="font-pixel text-xs text-ramen-red hover:text-ramen-orange transition-colors py-2 text-left"
          >
            Logout
          </button>
        </template>

        <!-- Not logged in: login link (mobile) -->
        <router-link
          v-else
          to="/login"
          class="font-pixel text-xs text-ramen-red hover:text-ramen-orange transition-colors py-2"
          @click="mobileMenuOpen = false"
        >
          Login
        </router-link>
      </div>
    </div>
  </nav>
</template>
