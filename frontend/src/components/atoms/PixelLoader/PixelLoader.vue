<script setup>
/**
 * PixelLoader — Animated loading spinner using a sprite sheet
 *
 * The sprite sheet is 240x18 (approx 13 frames of ~18x18 each).
 * CSS animation steps through each frame.
 */
import spriteSheet from '@/assets/Loading Screen.png'

defineProps({
  text: {
    type: String,
    default: 'Loading...',
  },
})
</script>

<template>
  <div class="flex flex-col items-center gap-2">
    <div
      class="pixel-render"
      :style="{
        width: '18px',
        height: '18px',
        backgroundImage: `url(${spriteSheet})`,
        backgroundRepeat: 'no-repeat',
        backgroundSize: '240px 18px',
        imageRendering: 'pixelated',
        transform: 'scale(3)',
        animation: 'sprite-walk 1s steps(13) infinite',
      }"
    ></div>
    <span class="font-pixel text-[8px] text-ramen-cream/50 mt-4">{{ text }}</span>
  </div>
</template>

<style scoped>
@keyframes sprite-walk {
  from { background-position: 0 0; }
  to { background-position: -240px 0; }
}
</style>
