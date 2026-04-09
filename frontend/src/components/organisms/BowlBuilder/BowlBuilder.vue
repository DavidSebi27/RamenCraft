<script setup>
import { computed } from 'vue'
import defaultBowlSprite from '@/assets/Graphics/blue.bowl.png'

/**
 * BowlBuilder — 3/4 perspective bowl visualization
 *
 * Renders the ramen bowl from a slight angle so you see the rim
 * as an ellipse and the side of the bowl below it.
 *
 * Layer order (z-index):
 *   z-10: Bowl body (side) + rim
 *   z-20: Broth fill (ellipse inside rim)
 *   z-30: Noodles (center of broth)
 *   z-35: Menma (always center, on top of noodles)
 *   z-36: Negi (always center, on top of menma)
 *   z-40: Oil drizzle
 *   z-50: Proteins (along the back/sides)
 *   z-55: Nori (sheet against back wall)
 *   z-60: Other toppings (scattered)
 *
 * Props:
 * - selections: { broth: [id], noodles: [id], oil: [ids], protein: [ids], topping: [ids] }
 * - ingredientMap: Object mapping ingredient ID → full ingredient object (with color)
 */
const props = defineProps({
  selections: {
    type: Object,
    default: () => ({
      broth: [],
      noodles: [],
      oil: [],
      protein: [],
      topping: [],
    }),
  },
  ingredientMap: {
    type: Object,
    default: () => ({}),
  },
  bowlSprite: {
    type: String,
    default: null,
  },
})

const activeBowlSprite = computed(() => props.bowlSprite || defaultBowlSprite)

// Helper to look up an ingredient by ID from the map
function getIng(id) {
  return props.ingredientMap[id] || null
}

const selectedBroth = computed(() =>
  props.selections.broth[0] ? getIng(props.selections.broth[0]) : null
)
const selectedNoodles = computed(() =>
  props.selections.noodles[0] ? getIng(props.selections.noodles[0]) : null
)
const selectedOils = computed(() =>
  props.selections.oil.map(id => getIng(id)).filter(Boolean)
)
const selectedProteins = computed(() =>
  props.selections.protein.map(id => getIng(id)).filter(Boolean)
)

// Menma, negi, nori get fixed positions; other toppings scatter
const selectedMenma = computed(() => {
  const id = props.selections.topping.find(id => {
    const ing = getIng(id)
    return ing && ing.name === 'Menma'
  })
  return id ? getIng(id) : null
})

const selectedNegi = computed(() => {
  const id = props.selections.topping.find(id => {
    const ing = getIng(id)
    return ing && ing.name === 'Negi'
  })
  return id ? getIng(id) : null
})

const selectedNori = computed(() => {
  const id = props.selections.topping.find(id => {
    const ing = getIng(id)
    return ing && ing.name === 'Nori'
  })
  return id ? getIng(id) : null
})

const otherToppings = computed(() =>
  props.selections.topping
    .map(id => getIng(id))
    .filter(t => t && t.name !== 'Menma' && t.name !== 'Negi' && t.name !== 'Nori')
)

// Protein positions by name — placed specifically inside the bowl
// Bowl container is 280px wide, bowl sprite is 260px centered
// Karaage: left, Tempura: right, Seitan: middle-back, Pork: middle above seitan, Ajitama: right front
const proteinPositionMap = {
  'Karaage':             { top: -88, left: 35,  z: 42 },  // left side, nudged right
  'Cauliflower Tempura': { top: -80, left: 205, z: 42, width: 85 },  // right, near rim
  'Seitan Katsu':        { top: -100, left: 70, z: 40 },  // middle-left back
  'Pork Chashu':         { top: -110, left: 65, z: 41 },  // above seitan, left
  'Ajitama':             { top: -72, left: 200, z: 43, width: 85 },  // right front, near rim
}
const defaultProteinPos = { top: -90, left: 80, z: 40 }

function proteinPos(name) {
  return proteinPositionMap[name] || defaultProteinPos
}

// Topping positions by name — placed specifically inside the bowl
const toppingPositionMap = {
  'Corn':         { top: -52, left: 40,  z: 42, width: 60 },  // under bean sprouts
  'Bean Sprouts': { top: -62, left: 40,  z: 41, width: 55 },  // down 10px
  'Spinach':      { top: -45, left: 120, z: 50, width: 50 },  // bigger, 5px down
  'Menma':        { top: -53, left: 115, z: 51, width: 50 },  // 5px down
  'Negi':         { top: -60, left: 120, z: 52, width: 45 },  // 5px down
  'Narutomaki':   { top: -50, left: 140, z: 53, width: 50 },  // 5px down
  'Nori':         { top: -90, left: 165, z: 50, width: 55 },  // right side
}
const defaultToppingPos = { top: -85, left: 80, z: 50, width: 45 }

function toppingPos(name) {
  return toppingPositionMap[name] || defaultToppingPos
}
</script>

<template>
  <div class="bowl-container">
    <!-- All sprites are 64x64 and designed to stack at the same position -->
    <!-- Layer order: bowl → broth → noodles → oils → proteins → toppings -->

    <!-- Bowl base -->
    <img :src="activeBowlSprite" alt="Bowl" class="bowl-layer pixel-render" />

    <!-- Broth (1px up to align with bowl rim) -->
    <img v-if="selectedBroth?.spriteIcon" :src="selectedBroth.spriteIcon" :alt="selectedBroth.name" class="bowl-layer pixel-render" style="z-index: 12; margin-top: -4px;" />

    <!-- Noodles (in-bowl version) -->
    <img v-if="selectedNoodles?.spriteBowl" :src="selectedNoodles.spriteBowl" :alt="selectedNoodles.name" class="bowl-layer pixel-render" style="z-index: 20; margin-top: -4px;" />

    <!-- Oils (in-soup version, each one 3px higher than the last) -->
    <template v-for="(oil, index) in selectedOils" :key="'oil-' + oil.id">
      <img v-if="oil.spriteSoup"
        :src="oil.spriteSoup" :alt="oil.name"
        class="bowl-layer pixel-render"
        :style="{ zIndex: 30 + index, marginTop: -(index * 3) + 'px' }"
      />
    </template>

    <!-- Proteins — each positioned by name -->
    <template v-for="(protein, index) in selectedProteins" :key="'protein-' + protein.id">
      <img v-if="protein.spriteIcon"
        :src="protein.spriteIcon" :alt="protein.name"
        class="bowl-item pixel-render"
        :style="{
          zIndex: proteinPos(protein.name).z,
          width: (proteinPos(protein.name).width || 75) + 'px',
          top: proteinPos(protein.name).top + 'px',
          left: proteinPos(protein.name).left + 'px',
        }"
      />
      <div v-else
        class="bowl-item pixel-render"
        :style="{
          zIndex: proteinPos(protein.name).z,
          width: '70px',
          height: '35px',
          top: proteinPos(protein.name).top + 'px',
          left: proteinPos(protein.name).left + 'px',
          backgroundColor: protein.color,
          borderRadius: '8px',
          border: '2px solid rgba(255,255,255,0.15)',
        }"
      ></div>
    </template>

    <!-- Toppings — each positioned by name -->
    <template v-for="toppingId in selections.topping" :key="'topping-' + toppingId">
      <img v-if="getIng(toppingId)?.spriteIcon"
        :src="getIng(toppingId).spriteIcon" :alt="getIng(toppingId).name"
        class="bowl-item pixel-render"
        :style="{
          zIndex: toppingPos(getIng(toppingId).name).z,
          width: toppingPos(getIng(toppingId).name).width + 'px',
          top: toppingPos(getIng(toppingId).name).top + 'px',
          left: toppingPos(getIng(toppingId).name).left + 'px',
        }"
      />
    </template>

    <!-- Steam animation — rises from the bowl when broth is selected -->
    <div v-if="selectedBroth" style="position: absolute; top: -130px; left: 50%; transform: translateX(-50%); width: 200px; height: 60px; z-index: 999; pointer-events: none;">
      <div class="steam-particle steam-1"></div>
      <div class="steam-particle steam-2"></div>
      <div class="steam-particle steam-3"></div>
      <div class="steam-particle steam-4"></div>
      <div class="steam-particle steam-5"></div>
      <div class="steam-particle steam-6"></div>
      <div class="steam-particle steam-7"></div>
      <div class="steam-particle steam-8"></div>
    </div>

    <!-- Empty state hint (always occupies space to prevent layout shift) -->
    <div class="empty-hint">
      <span v-if="!selectedBroth" class="font-pixel text-[8px] text-ramen-cream/30 text-center px-8">
        Pick a broth to start building
      </span>
      <span v-else-if="!selectedNoodles" class="font-pixel text-[8px] text-ramen-cream/30 text-center px-8">
        Add at least a broth and noodles
      </span>
      <span v-else class="font-pixel text-[8px] invisible">placeholder</span>
    </div>
  </div>
</template>

<style scoped>
.bowl-container {
  position: relative;
  width: 280px;
  height: 180px;
  margin: 0 auto;
  overflow: visible;
}

/* Scale down on small screens while keeping all positioning intact */
@media (max-width: 640px) {
  .bowl-container {
    transform: scale(0.75);
    transform-origin: center center;
    margin-top: -20px;
    margin-bottom: -20px;
  }
}

/*
 * Bowl geometry (3/4 inside, 1/4 side):
 *   Rim ellipse: 280×70, centered at top:30
 *   Rim midpoint (where side attaches): top:30 + 35 = top:65
 *   Side height: 65px (short — just 1/4 of the bowl)
 *   Side bottom: 65 + 65 = 130, plus the rounded bottom ~195
 *   Side width at top: 280px (matches rim)
 */

/* Individual items positioned inside the bowl */
.bowl-item {
  position: absolute;
  height: auto;
  object-fit: contain;
}

/* All ingredient layers stack at the same position/size */
.bowl-layer {
  position: absolute;
  top: -130px;
  left: 50%;
  transform: translateX(-50%);
  width: 260px;
  height: 260px;
  z-index: 10;
  object-fit: contain;
}

/* Legacy CSS bowl (kept for reference, hidden by sprite) */
.bowl-side {
  position: absolute;
  top: 78px;
  left: 50%;
  transform: translateX(-50%);
  width: 355px;
  height: 165px;
  background: linear-gradient(
    to bottom,
    #4a3728 0%,
    #5c4535 20%,
    #3d2e20 60%,
    #2e2118 100%
  );
  border-radius: 0 0 178px 178px / 0 0 127px 127px;
  z-index: 10;
  border-bottom: 4px solid #2a1d14;
  border-left: 3px solid #3d2e20;
  border-right: 3px solid #3d2e20;
}

/* Bowl rim — thick elliptical top edge, flush with side */
.bowl-rim {
  position: absolute;
  top: 38px;
  left: 50%;
  transform: translateX(-50%);
  width: 355px;
  height: 89px;
  background: linear-gradient(
    to bottom,
    #6b5344 0%,
    #5c4535 50%,
    #4a3728 100%
  );
  border-radius: 50%;
  z-index: 12;
  border: 3px solid #3d2e20;
}

/* Inner bowl surface visible through the rim */
.bowl-inner {
  position: absolute;
  top: 50px;
  left: 50%;
  transform: translateX(-50%);
  width: 325px;
  height: 69px;
  background: #1a1a2e;
  border-radius: 50%;
  z-index: 13;
}

.broth-fill {
  position: absolute;
  top: 49px;
  left: 50%;
  transform: translateX(-50%);
  width: 320px;
  height: 71px;
  border-radius: 50%;
  z-index: 20;
  transition: background-color 0.3s ease;
}

.noodle-layer {
  position: absolute;
  top: 53px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 30;
}

.noodle-shape {
  width: 178px;
  height: 53px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  position: relative;
  opacity: 0.9;
}

.noodle-lines {
  position: absolute;
  inset: 0;
  overflow: hidden;
}

.noodle-strand {
  position: absolute;
  width: 100%;
  height: 3px;
  border-radius: 2px;
  opacity: 0.6;
}

.noodle-strand:nth-child(1) { top: 20%; }
.noodle-strand:nth-child(2) { top: 32%; }
.noodle-strand:nth-child(3) { top: 44%; }
.noodle-strand:nth-child(4) { top: 56%; }
.noodle-strand:nth-child(5) { top: 68%; }
.noodle-strand:nth-child(6) { top: 80%; }

/* Menma — bamboo shoot strips, center of bowl */
.menma-layer {
  position: absolute;
  top: 56px;
  left: 50%;
  transform: translateX(-50%);
  width: 76px;
  height: 38px;
  z-index: 35;
}

.menma-piece {
  position: absolute;
  width: 36px;
  height: 10px;
  border-radius: 2px;
  top: 10px;
  left: 8px;
  transform: rotate(-8deg);
  border: 1px solid rgba(0,0,0,0.15);
}

.menma-piece-2 {
  top: 18px;
  left: 20px;
  transform: rotate(5deg);
}

.menma-inner {
  position: absolute;
  top: 2px;
  left: 3px;
  right: 3px;
  height: 2px;
  border-radius: 1px;
  opacity: 0.6;
}

/* Negi — small green onion rings */
.negi-layer {
  position: absolute;
  inset: 0;
  z-index: 36;
}

.negi-ring {
  position: absolute;
  width: 10px;
  height: 10px;
  border-radius: 50%;
  border: 2px solid rgba(0,0,0,0.15);
}

.negi-center {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 3px;
  height: 3px;
  border-radius: 50%;
  background: rgba(255,255,255,0.4);
}

.oil-layer {
  position: absolute;
  z-index: 40;
  pointer-events: none;
}

.oil-drizzle {
  position: absolute;
  border-radius: 50%;
  z-index: 40;
}

.protein-piece {
  position: absolute;
  z-index: 50;
}

.protein-shape {
  width: 60px;
  height: 35px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: 'Press Start 2P', cursive;
  font-size: 5px;
  color: white;
  border: 1px solid rgba(255,255,255,0.2);
  text-shadow: 1px 1px 1px rgba(0,0,0,0.5);
}

.topping-piece {
  position: absolute;
  z-index: 60;
}

.topping-shape {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: 'Press Start 2P', cursive;
  font-size: 5px;
  color: white;
  border: 1px solid rgba(255,255,255,0.2);
  text-shadow: 1px 1px 1px rgba(0,0,0,0.5);
}

/* Nori — rectangular sheet leaning against the back wall */
.nori-piece {
  position: absolute;
  top: 8%;
  right: 22%;
  z-index: 55;
}

.nori-sheet {
  width: 28px;
  height: 46px;
  border-radius: 2px;
  position: relative;
  transform: perspective(60px) rotateX(-8deg);
  border: 1px solid rgba(255,255,255,0.1);
}

.nori-shine {
  position: absolute;
  top: 4px;
  left: 3px;
  width: 6px;
  height: 14px;
  background: rgba(255,255,255,0.08);
  border-radius: 1px;
}

.empty-hint {
  position: absolute;
  inset: 0;
  top: 135px;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 70;
}

/* Steam — pixel-art styled rising particles
 * Container spans from top of bowl-container to the broth surface.
 * Particles start at bottom (broth) and rise to top (above bowl). */
.steam-container {
  position: absolute;
  top: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 200px;
  height: 60px;
  z-index: 80;
  pointer-events: none;
}

.steam-particle {
  position: absolute;
  bottom: 0;
  background: white;
  border-radius: 0;
  image-rendering: pixelated;
  opacity: 0;
  animation: steam-rise ease-out infinite;
}

/* Each particle spread across the full bowl width */
.steam-1 {
  width: 6px; height: 6px;
  left: 15%;
  animation-duration: 2.4s;
  animation-delay: 0s;
}
.steam-2 {
  width: 8px; height: 8px;
  left: 70%;
  animation-duration: 2.8s;
  animation-delay: 0.5s;
}
.steam-3 {
  width: 6px; height: 6px;
  left: 40%;
  animation-duration: 2.1s;
  animation-delay: 1.0s;
}
.steam-4 {
  width: 10px; height: 10px;
  left: 80%;
  animation-duration: 3.2s;
  animation-delay: 0.3s;
}
.steam-5 {
  width: 6px; height: 6px;
  left: 25%;
  animation-duration: 2.6s;
  animation-delay: 1.5s;
}
.steam-6 {
  width: 8px; height: 8px;
  left: 55%;
  animation-duration: 3.0s;
  animation-delay: 0.8s;
}
.steam-7 {
  width: 6px; height: 6px;
  left: 35%;
  animation-duration: 2.3s;
  animation-delay: 1.2s;
}
.steam-8 {
  width: 8px; height: 8px;
  left: 65%;
  animation-duration: 2.7s;
  animation-delay: 0.2s;
}

@keyframes steam-rise {
  0% {
    transform: translateY(0) translateX(0) scale(1);
    opacity: 0;
  }
  10% {
    opacity: 0.5;
  }
  40% {
    opacity: 0.3;
  }
  100% {
    transform: translateY(-50px) translateX(var(--drift, 8px)) scale(1.8);
    opacity: 0;
  }
}

/* Alternate drift directions */
.steam-1 { --drift: -6px; }
.steam-2 { --drift: 10px; }
.steam-3 { --drift: -12px; }
.steam-4 { --drift: 5px; }
.steam-5 { --drift: -8px; }
.steam-6 { --drift: 14px; }
.steam-7 { --drift: -10px; }
.steam-8 { --drift: 7px; }
</style>
