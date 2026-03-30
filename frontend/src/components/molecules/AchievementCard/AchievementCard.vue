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
    class="border p-3 transition-all duration-500"
    :class="unlocked
      ? 'border-ramen-gold bg-ramen-dark scale-100'
      : 'border-ramen-brown bg-ramen-darker opacity-40 scale-[0.98]'
    "
  >
    <div class="flex items-start gap-3">
      <!-- Achievement icon -->
      <div
        class="w-10 h-10 flex items-center justify-center text-lg rounded shrink-0 transition-all duration-500"
        :class="unlocked ? 'bg-ramen-gold/20 shadow-md shadow-ramen-gold/30' : 'bg-ramen-brown/30'"
      >
        {{ unlocked ? '&#9733;' : '&#128274;' }}
      </div>

      <div>
        <h4
          class="font-pixel text-[8px]"
          :class="unlocked ? 'text-ramen-gold' : 'text-ramen-cream'"
        >
          {{ achievement.name }}
        </h4>
        <p class="text-[10px] text-ramen-cream/50 mt-1">{{ achievement.description }}</p>
        <p v-if="unlocked && achievement.unlockedAt" class="font-pixel text-[7px] text-ramen-neon mt-1">
          Unlocked {{ new Date(achievement.unlockedAt).toLocaleDateString() }}
        </p>
      </div>
    </div>
  </div>
</template>