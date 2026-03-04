<script setup>
import NavBar from '@/components/organisms/NavBar/NavBar.vue'
</script>

<template>
  <div class="min-h-screen bg-ramen-darker">
    <NavBar />
    <div class="max-w-4xl mx-auto p-6">
      <h1 class="font-pixel text-xl text-ramen-orange mb-6">Admin Dashboard</h1>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <router-link
          to="/admin/ingredients"
          class="bg-ramen-dark border border-ramen-brown p-4 hover:border-ramen-orange transition-colors"
        >
          <h2 class="font-pixel text-sm text-ramen-cream mb-2">Ingredients</h2>
          <p class="text-sm text-ramen-cream/60">Manage broth, noodles, oils, proteins, toppings</p>
        </router-link>
        <router-link
          to="/admin/pairings"
          class="bg-ramen-dark border border-ramen-brown p-4 hover:border-ramen-orange transition-colors"
        >
          <h2 class="font-pixel text-sm text-ramen-cream mb-2">Pairings</h2>
          <p class="text-sm text-ramen-cream/60">Edit ingredient combo scoring rules</p>
        </router-link>
        <router-link
          to="/admin/achievements"
          class="bg-ramen-dark border border-ramen-brown p-4 hover:border-ramen-orange transition-colors"
        >
          <h2 class="font-pixel text-sm text-ramen-cream mb-2">Achievements</h2>
          <p class="text-sm text-ramen-cream/60">Create and edit achievement badges</p>
        </router-link>
        <router-link
          to="/admin/users"
          class="bg-ramen-dark border border-ramen-brown p-4 hover:border-ramen-orange transition-colors"
        >
          <h2 class="font-pixel text-sm text-ramen-cream mb-2">Users</h2>
          <p class="text-sm text-ramen-cream/60">View and manage player accounts</p>
        </router-link>
      </div>
    </div>
  </div>
</template>
