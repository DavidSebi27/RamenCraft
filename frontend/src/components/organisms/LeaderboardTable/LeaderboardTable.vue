<script setup>
/**
 * LeaderboardTable — Displays a ranked list of top players
 *
 * Uses hardcoded fake data for Phase 1.
 * Will be connected to GET /api/leaderboard in Phase 3.
 */

// Fake leaderboard data for static display
const players = [
  { rank: 1, name: 'NoodleMaster99', xp: 18200, bowls: 342, tier: 'taisho' },
  { rank: 2, name: 'BrothBoss', xp: 15800, bowls: 289, tier: 'taisho' },
  { rank: 3, name: 'RamenSenpai', xp: 12400, bowls: 231, tier: 'shokunin' },
  { rank: 4, name: 'MisoGirl', xp: 8900, bowls: 178, tier: 'shokunin' },
  { rank: 5, name: 'TonkotsuKing', xp: 6200, bowls: 145, tier: 'shokunin' },
  { rank: 6, name: 'SlurpLord', xp: 4100, bowls: 98, tier: 'tsuu' },
  { rank: 7, name: 'Chashu4Life', xp: 2800, bowls: 67, tier: 'tsuu' },
  { rank: 8, name: 'EggIsLife', xp: 1500, bowls: 42, tier: 'jouren' },
  { rank: 9, name: 'NoriNinja', xp: 800, bowls: 23, tier: 'jouren' },
  { rank: 10, name: 'NewbieCook', xp: 150, bowls: 5, tier: 'minarai' },
]

// Color for each rank tier
const tierColors = {
  minarai: '#9CA3AF',
  jouren: '#60A5FA',
  tsuu: '#A78BFA',
  shokunin: '#F59E0B',
  taisho: '#EF4444',
}
</script>

<template>
  <div class="overflow-x-auto">
    <table class="w-full text-left">
      <thead>
        <tr class="border-b border-ramen-brown">
          <th class="font-pixel text-[8px] text-ramen-cream/40 pb-2 pr-4">#</th>
          <th class="font-pixel text-[8px] text-ramen-cream/40 pb-2 pr-4">Player</th>
          <th class="font-pixel text-[8px] text-ramen-cream/40 pb-2 pr-4">XP</th>
          <th class="font-pixel text-[8px] text-ramen-cream/40 pb-2 hidden sm:table-cell">Bowls</th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="player in players"
          :key="player.rank"
          class="border-b border-ramen-brown/30"
        >
          <!-- Rank number -->
          <td class="py-2 pr-4">
            <span
              class="font-pixel text-sm"
              :class="player.rank <= 3 ? 'text-ramen-gold' : 'text-ramen-cream/60'"
            >
              {{ player.rank }}
            </span>
          </td>

          <!-- Player name + tier indicator -->
          <td class="py-2 pr-4">
            <div class="flex items-center gap-2">
              <span class="w-2 h-2 rounded-sm" :style="{ backgroundColor: tierColors[player.tier] }"></span>
              <span class="text-sm text-ramen-cream">{{ player.name }}</span>
            </div>
          </td>

          <!-- XP -->
          <td class="py-2 pr-4">
            <span class="font-pixel text-[10px] text-ramen-gold">{{ player.xp.toLocaleString() }}</span>
          </td>

          <!-- Bowls served -->
          <td class="py-2 hidden sm:table-cell">
            <span class="text-sm text-ramen-cream/50">{{ player.bowls }}</span>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>