<script setup>
/**
 * AchievementToast — shows a notification when achievements are unlocked after serving.
 *
 * Displays each newly unlocked achievement with its name and description.
 * Auto-dismisses after 5 seconds or on click.
 */
import { onMounted, ref } from 'vue'

defineProps({
  achievements: {
    type: Array,
    required: true,
  },
})

const emit = defineEmits(['dismiss'])

const visible = ref(false)

onMounted(() => {
  // Fade in after a short delay
  setTimeout(() => {
    visible.value = true
  }, 300)

  // Auto-dismiss after 5 seconds
  setTimeout(() => {
    emit('dismiss')
  }, 5000)
})
</script>

<template>
  <div
    class="fixed top-20 right-4 z-50 space-y-2 transition-all duration-500"
    :class="visible ? 'opacity-100 translate-x-0' : 'opacity-0 translate-x-8'"
  >
    <div
      v-for="a in achievements"
      :key="a.id"
      class="bg-ramen-dark border-2 border-ramen-gold p-3 max-w-xs cursor-pointer
             shadow-lg shadow-ramen-gold/20"
      @click="emit('dismiss')"
    >
      <div class="flex items-center gap-2 mb-1">
        <span class="text-ramen-gold text-lg">&#9733;</span>
        <span class="font-pixel text-[10px] text-ramen-gold uppercase">Achievement Unlocked!</span>
      </div>
      <div class="font-pixel text-xs text-ramen-cream">{{ a.name }}</div>
      <div class="font-pixel text-[8px] text-ramen-cream/50 mt-0.5">{{ a.description }}</div>
    </div>
  </div>
</template>
