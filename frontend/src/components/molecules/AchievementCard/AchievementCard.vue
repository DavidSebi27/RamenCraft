<script setup>
/**
 * AchievementCard — Displays an achievement with locked/unlocked state
 *
 * Props:
 * - achievement: Object with { name, description, icon }
 * - unlocked: Whether the player has earned this achievement
 */
defineProps({
  achievement: {
    type: Object,
    required: true,
  },
  unlocked: {
    type: Boolean,
    default: false,
  },
})
</script>

<template>
  <div
    class="border p-3 transition-colors"
    :class="unlocked
      ? 'border-ramen-gold bg-ramen-dark'
      : 'border-ramen-brown bg-ramen-darker opacity-50'
    "
  >
    <div class="flex items-start gap-3">
      <!-- Achievement icon placeholder -->
      <div
        class="w-10 h-10 flex items-center justify-center text-lg rounded"
        :class="unlocked ? 'bg-ramen-gold/20' : 'bg-ramen-brown/30'"
      >
        {{ unlocked ? '⭐' : '🔒' }}
      </div>

      <div>
        <h4 class="font-pixel text-[8px] text-ramen-cream">{{ achievement.name }}</h4>
        <p class="text-[10px] text-ramen-cream/50 mt-1">{{ achievement.description }}</p>
      </div>
    </div>
  </div>
</template>