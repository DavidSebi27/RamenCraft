<script setup>
// HomePage — Landing page for RamenCraft
// Will be expanded with pixel art hero section, play button, etc.
</script>

<template>
  <div class="min-h-screen flex flex-col items-center justify-center bg-ramen-darker">
    <h1 class="font-pixel text-4xl md:text-5xl text-ramen-orange mb-4">
      RamenCraft
    </h1>
    <p class="font-pixel text-xs md:text-sm text-ramen-cream mb-8">
      Build. Serve. Rank Up.
    </p>
    <router-link
      to="/play"
      class="font-pixel text-sm bg-ramen-red text-ramen-cream px-6 py-3 hover:bg-ramen-orange transition-colors"
    >
      PLAY
    </router-link>
  </div>
</template>
