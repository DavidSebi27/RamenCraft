<script setup>
import NavBar from '@/components/organisms/NavBar/NavBar.vue'
import { getStoredUser } from '@/services/api'

const user = getStoredUser()

const sections = [
  {
    path: '/admin/ingredients',
    icon: '材',
    title: 'Ingredients',
    jp: '材料',
    desc: 'Broth, noodles, oils, proteins, toppings',
  },
  {
    path: '/admin/pairings',
    icon: '相',
    title: 'Pairings',
    jp: '相性',
    desc: 'Ingredient combo scoring rules',
  },
  {
    path: '/admin/achievements',
    icon: '実',
    title: 'Achievements',
    jp: '実績',
    desc: 'Create and edit achievement badges',
  },
  {
    path: '/admin/users',
    icon: '人',
    title: 'Users',
    jp: 'ユーザー',
    desc: 'View and manage player accounts',
  },
]
</script>

<template>
  <div class="min-h-screen bg-ramen-darker">
    <NavBar />
    <div class="max-w-4xl mx-auto p-6">

      <!-- Welcome header -->
      <div class="mb-8 text-center">
        <h1 class="font-pixel text-xl text-ramen-orange mb-1">Admin Kitchen</h1>
        <p class="font-pixel text-xs text-ramen-cream/30">管理画面</p>
        <div class="flex items-center justify-center gap-2 mt-3">
          <div class="h-px w-16 bg-gradient-to-r from-transparent to-ramen-orange/40"></div>
          <span class="text-ramen-cream/20 text-xs">◆</span>
          <div class="h-px w-16 bg-gradient-to-l from-transparent to-ramen-orange/40"></div>
        </div>
        <p class="text-sm text-ramen-cream/40 mt-3">
          Welcome back, <span class="text-ramen-gold">{{ user?.username || 'Chef' }}</span>
        </p>
      </div>

      <!-- Section cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <router-link
          v-for="section in sections"
          :key="section.path"
          :to="section.path"
          class="bg-ramen-dark border border-ramen-brown/50 p-5 group hover:border-ramen-orange/60 hover:scale-[1.02] transition-all"
        >
          <div class="flex items-start gap-4">
            <div class="w-12 h-12 bg-ramen-darker border border-ramen-brown flex items-center justify-center shrink-0 group-hover:border-ramen-orange/40 transition-colors">
              <span class="font-pixel text-sm text-ramen-orange group-hover:text-ramen-gold transition-colors">{{ section.icon }}</span>
            </div>
            <div>
              <div class="flex items-center gap-2 mb-1">
                <h2 class="font-pixel text-sm text-ramen-cream group-hover:text-ramen-orange transition-colors">
                  {{ section.title }}
                </h2>
                <span class="font-pixel text-xs text-ramen-cream/20">{{ section.jp }}</span>
              </div>
              <p class="text-sm text-ramen-cream/40">{{ section.desc }}</p>
            </div>
          </div>

          <!-- Decorative arrow -->
          <div class="text-right mt-2 opacity-0 group-hover:opacity-100 transition-opacity">
            <span class="font-pixel text-xs text-ramen-orange">Enter ►</span>
          </div>
        </router-link>
      </div>
    </div>
  </div>
</template>
