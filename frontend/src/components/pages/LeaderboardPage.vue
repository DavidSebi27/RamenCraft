<script setup>
// LeaderboardPage — Global rankings with fake data
import NavBar from '@/components/organisms/NavBar/NavBar.vue'
import LeaderboardTable from '@/components/organisms/LeaderboardTable/LeaderboardTable.vue'
</script>

<template>
  <div class="min-h-screen bg-ramen-darker">
    <NavBar />
    <div class="max-w-3xl mx-auto p-4">
      <h1 class="font-pixel text-xl text-ramen-orange mb-6">Leaderboard</h1>
      <LeaderboardTable />
    </div>
  </div>
</template>