<script setup>
import { computed } from 'vue'

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
})

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

// Protein positions — inside the broth ellipse (top ~17-28%, left within bowl)
const proteinPositions = [
  { top: '17%', left: '8%' },
  { top: '15%', left: '50%', transform: 'translateX(-50%)' },
  { top: '17%', right: '8%' },
]

// Other topping positions — scattered inside the broth ellipse
const toppingPositions = [
  { top: '19%', left: '15%' },
  { top: '24%', left: '60%' },
  { top: '17%', left: '65%' },
  { top: '23%', left: '10%' },
  { top: '27%', left: '40%' },
]
</script>

<template>
  <div class="bowl-container">
    <!-- Bowl side (visible ceramic body below the rim) -->
    <div class="bowl-side"></div>

    <!-- Bowl rim (thick elliptical top edge) -->
    <div class="bowl-rim"></div>

    <!-- Inner bowl surface -->
    <div class="bowl-inner"></div>

    <!-- Broth fill -->
    <div v-if="selectedBroth" class="broth-fill"
      :style="{ backgroundColor: selectedBroth.color + 'CC' }"
    ></div>

    <!-- Noodles — center of the bowl -->
    <div v-if="selectedNoodles" class="noodle-layer">
      <div class="noodle-shape" :style="{ backgroundColor: selectedNoodles.color }">
        <div class="noodle-lines">
          <div v-for="i in 6" :key="'line-'+i" class="noodle-strand"
            :style="{
              backgroundColor: selectedNoodles.color,
              filter: `brightness(${0.85 + (i % 3) * 0.1})`,
              transform: `rotate(${-15 + i * 6}deg)`,
            }"
          ></div>
        </div>
        <span class="font-pixel text-[6px] text-ramen-dark/50 relative" style="z-index: 1;">
          {{ selectedNoodles.nameJp }}
        </span>
      </div>
    </div>

    <!-- Menma — always dead center, on top of noodles -->
    <div v-if="selectedMenma" class="menma-layer">
      <div class="menma-piece" :style="{ backgroundColor: selectedMenma.color }">
        <div class="menma-inner" :style="{ backgroundColor: selectedMenma.color, filter: 'brightness(1.2)' }"></div>
      </div>
      <div class="menma-piece menma-piece-2" :style="{ backgroundColor: selectedMenma.color, filter: 'brightness(0.9)' }">
        <div class="menma-inner" :style="{ backgroundColor: selectedMenma.color }"></div>
      </div>
    </div>

    <!-- Negi — right on top of menma, center -->
    <div v-if="selectedNegi" class="negi-layer">
      <div v-for="i in 5" :key="'negi-'+i"
        class="negi-ring"
        :style="{
          backgroundColor: selectedNegi.color,
          left: (41 + i * 5) + '%',
          top: (23 + (i % 2) * 4) + '%',
          opacity: 0.8 + (i % 3) * 0.1,
        }"
      >
        <div class="negi-center"></div>
      </div>
    </div>

    <!-- Oil drizzle — spread across broth surface, supports all 5 oils -->
    <div v-for="(oil, index) in selectedOils" :key="'oil-' + oil.id"
      class="oil-drizzle"
      :style="{
        backgroundColor: oil.color,
        top: [19, 22, 17, 25, 20][index % 5] + '%',
        left: [15, 35, 55, 25, 48][index % 5] + '%',
        width: '50px',
        height: '20px',
        opacity: 0.35,
        transform: `rotate(${[-20, 15, -10, 30, -5][index % 5]}deg)`,
      }"
    ></div>

    <!-- Proteins — along the back of the bowl -->
    <div v-for="(protein, index) in selectedProteins"
      :key="'protein-' + protein.id"
      class="protein-piece"
      :style="proteinPositions[index % proteinPositions.length]"
    >
      <div class="protein-shape" :style="{ backgroundColor: protein.color }">
        {{ protein.name.slice(0, 4) }}
      </div>
    </div>

    <!-- Nori — sheet leaning against the back wall of the bowl -->
    <div v-if="selectedNori" class="nori-piece">
      <div class="nori-sheet" :style="{ backgroundColor: selectedNori.color }">
        <div class="nori-shine"></div>
      </div>
    </div>

    <!-- Other toppings (not menma/negi/nori) -->
    <div v-for="(topping, index) in otherToppings"
      :key="'topping-' + topping.id"
      class="topping-piece"
      :style="toppingPositions[index % toppingPositions.length]"
    >
      <div class="topping-shape" :style="{ backgroundColor: topping.color }">
        {{ topping.name.slice(0, 2) }}
      </div>
    </div>

    <!-- Steam animation — rises from the bowl when broth is selected -->
    <div v-if="selectedBroth" style="position: absolute; top: 0; left: 50%; transform: translateX(-50%); width: 300px; height: 60px; z-index: 999; pointer-events: none;">
      <div class="steam-particle steam-1"></div>
      <div class="steam-particle steam-2"></div>
      <div class="steam-particle steam-3"></div>
      <div class="steam-particle steam-4"></div>
      <div class="steam-particle steam-5"></div>
      <div class="steam-particle steam-6"></div>
      <div class="steam-particle steam-7"></div>
      <div class="steam-particle steam-8"></div>
    </div>

    <!-- Empty state hint -->
    <div v-if="!selectedBroth && !selectedNoodles"
      class="empty-hint"
    >
      <span class="font-pixel text-[8px] text-ramen-cream/30 text-center px-8">
        Pick a broth to start building
      </span>
    </div>
  </div>
</template>

<style scoped>
.bowl-container {
  position: relative;
  width: 380px;
  height: 330px;
  margin: 0 auto;
  overflow: visible;
}

/*
 * Bowl geometry (3/4 inside, 1/4 side):
 *   Rim ellipse: 280×70, centered at top:30
 *   Rim midpoint (where side attaches): top:30 + 35 = top:65
 *   Side height: 65px (short — just 1/4 of the bowl)
 *   Side bottom: 65 + 65 = 130, plus the rounded bottom ~195
 *   Side width at top: 280px (matches rim)
 */

/* Bowl side — short ceramic body, width matches rim exactly */
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
