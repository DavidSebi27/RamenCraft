<script setup>
import NavBar from '@/components/organisms/NavBar/NavBar.vue'
</script>

<template>
  <div class="min-h-screen bg-ramen-darker">
    <NavBar />
    <div class="max-w-4xl mx-auto p-6">
      <h1 class="font-pixel text-xl text-ramen-orange mb-4">Manage Ingredients</h1>
      <p class="text-sm text-ramen-cream/60 mb-4">CRUD operations will be connected to API in Phase 3.</p>
      <router-link to="/admin" class="text-ramen-neon text-sm hover:underline">&larr; Back to Admin</router-link>
    </div>
  </div>
</template>
