<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import bgMusic from '@/assets/18 High Quality 8-bit Musics/17. The Quiet Spy.mp3'

const router = useRouter()
const auth = useAuthStore()

// Controls whether the mobile hamburger menu is open or closed
const mobileMenuOpen = ref(false)

// Music player — singleton audio instance (survives component re-mounts)
if (!window.__ramenMusic) {
  window.__ramenMusic = new Audio(bgMusic)
  window.__ramenMusic.loop = true
  window.__ramenMusic.volume = 0.15
  window.__ramenMusicPlaying = true
  // Start on first user interaction (browser autoplay policy)
  const startMusic = () => {
    if (window.__ramenMusicPlaying) {
      window.__ramenMusic.play().catch(() => {})
    }
    document.removeEventListener('click', startMusic)
  }
  document.addEventListener('click', startMusic)
}
const audio = window.__ramenMusic
const musicPlaying = ref(window.__ramenMusicPlaying)

function toggleMusic() {
  if (musicPlaying.value) {
    audio.pause()
  } else {
    audio.play()
  }
  musicPlaying.value = !musicPlaying.value
  window.__ramenMusicPlaying = musicPlaying.value
}

function handleLogout() {
  auth.logout()
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
            v-if="auth.isAdmin"
            to="/admin"
            class="font-pixel text-xs text-ramen-neon hover:text-ramen-orange transition-colors"
          >
            Admin
          </router-link>

          <!-- Logged in: show username + logout -->
          <template v-if="auth.isLoggedIn">
            <span class="font-pixel text-xs text-ramen-gold">{{ auth.username }}</span>
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

          <!-- Music toggle -->
          <button
            @click="toggleMusic"
            class="ml-2 text-ramen-cream/60 hover:text-ramen-gold transition-colors"
            :title="musicPlaying ? 'Mute music' : 'Play music'"
          >
            <svg v-if="musicPlaying" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5" fill="currentColor" />
              <path d="M15.54 8.46a5 5 0 0 1 0 7.07" />
              <path d="M19.07 4.93a10 10 0 0 1 0 14.14" />
            </svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5" fill="currentColor" />
              <line x1="23" y1="9" x2="17" y2="15" />
              <line x1="17" y1="9" x2="23" y2="15" />
            </svg>
          </button>
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
          v-if="auth.isAdmin"
          to="/admin"
          class="font-pixel text-xs text-ramen-neon hover:text-ramen-orange transition-colors py-2"
          @click="mobileMenuOpen = false"
        >
          Admin
        </router-link>

        <!-- Logged in: username + logout (mobile) -->
        <template v-if="auth.isLoggedIn">
          <span class="font-pixel text-xs text-ramen-gold py-2">{{ auth.username }}</span>
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
