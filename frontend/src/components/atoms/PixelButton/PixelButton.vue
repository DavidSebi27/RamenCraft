<script setup>
/**
 * PixelButton — Reusable pixel art styled button
 *
 * Props:
 * - label: Text shown on the button
 * - variant: 'primary' (red), 'secondary' (brown), 'danger' (dark red)
 * - size: 'sm', 'md', 'lg'
 * - disabled: Grays out and disables the button
 *
 * Emits:
 * - click: When button is clicked (unless disabled)
 */
const props = defineProps({
  label: {
    type: String,
    required: true,
  },
  variant: {
    type: String,
    default: 'primary',
    validator: (v) => ['primary', 'secondary', 'danger'].includes(v),
  },
  size: {
    type: String,
    default: 'md',
    validator: (v) => ['sm', 'md', 'lg'].includes(v),
  },
  disabled: {
    type: Boolean,
    default: false,
  },
})

import { playSound } from '@/utils/sounds'

const emit = defineEmits(['click'])

function handleClick() {
  if (!props.disabled) {
    playSound('click')
    emit('click')
  }
}
</script>

<template>
  <button
    :disabled="disabled"
    class="font-pixel transition-colors inline-block border-0 select-none"
    :class="[
      // Variant colors
      variant === 'primary' && !disabled ? 'bg-ramen-red text-ramen-cream hover:bg-ramen-orange' : '',
      variant === 'secondary' && !disabled ? 'bg-ramen-brown text-ramen-cream hover:bg-ramen-brown-light' : '',
      variant === 'danger' && !disabled ? 'bg-red-900 text-ramen-cream hover:bg-red-700' : '',
      // Disabled state
      disabled ? 'bg-gray-700 text-gray-500 cursor-not-allowed' : 'cursor-pointer active:translate-y-0.5',
      // Size
      size === 'sm' ? 'text-[8px] px-3 py-1.5' : '',
      size === 'md' ? 'text-xs px-4 py-2.5' : '',
      size === 'lg' ? 'text-sm px-6 py-3' : '',
    ]"
    @click="handleClick"
  >
    {{ label }}
  </button>
</template>