<script setup>
import { ref } from 'vue'

// Controls whether the mobile hamburger menu is open or closed
const mobileMenuOpen = ref(false)

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

          <!-- Login button, styled differently -->
          <router-link
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
          <!-- Hamburger icon (3 lines) when closed, X when open -->
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
        <router-link
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
