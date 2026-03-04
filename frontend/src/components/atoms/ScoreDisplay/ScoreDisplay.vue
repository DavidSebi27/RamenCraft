<script setup>
/**
 * ScoreDisplay — Shows a labeled score value
 *
 * Used for displaying tastiness score, nutrition score, total score, etc.
 *
 * Props:
 * - label: Text label (e.g., "Tastiness", "Nutrition")
 * - score: The numeric score value
 * - maxScore: Maximum possible score (used for context)
 */
defineProps({
  label: {
    type: String,
    required: true,
  },
  score: {
    type: Number,
    default: 0,
  },
  maxScore: {
    type: Number,
    default: 100,
  },
})
</script>

<template>
  <div class="text-center">
    <div class="font-pixel text-[8px] text-ramen-cream/60 mb-1 uppercase">{{ label }}</div>
    <div class="font-pixel text-lg text-ramen-gold">{{ score }}</div>
    <div class="text-[10px] text-ramen-cream/40">/ {{ maxScore }}</div>
  </div>
</template>