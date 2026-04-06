<script setup>
/**
 * PlayPage — Main game screen (wizard-style layout)
 *
 * Bowl centered on screen, categories shown one at a time below it.
 * Player steps through: Broth → Noodles → Oil → Protein → Toppings → Serve.
 * Skip button lets you skip any category.
 */
import { ref, onMounted, computed } from 'vue'
import NavBar from '@/components/organisms/NavBar/NavBar.vue'
import BowlBuilder from '@/components/organisms/BowlBuilder/BowlBuilder.vue'
import IngredientCard from '@/components/molecules/IngredientCard/IngredientCard.vue'
import PixelButton from '@/components/atoms/PixelButton/PixelButton.vue'
import XPBar from '@/components/atoms/XPBar/XPBar.vue'
import AchievementToast from '@/components/molecules/AchievementToast/AchievementToast.vue'
import PixelLoader from '@/components/atoms/PixelLoader/PixelLoader.vue'
import { playSound } from '@/utils/sounds'
import { useIngredientStore } from '@/stores/ingredients'
import { useBowlStore } from '@/stores/bowl'
import { useFavoritesStore } from '@/stores/favorites'
import restaurantBg from '@/assets/Graphics/restaurant.inside.png'

const ingredientStore = useIngredientStore()
const bowlStore = useBowlStore()
const favoritesStore = useFavoritesStore()

// Achievements unlocked from the latest serve (read from serve result)
const newAchievements = computed(() => bowlStore.serveResult?.newAchievements || [])

// Bowl color selection (step 0, purely cosmetic)
import blueBowl from '@/assets/Graphics/blue.bowl.png'
import greenBowl from '@/assets/Graphics/green.bowl.png'
import orangeBowl from '@/assets/Graphics/orange.bowl.png'
import redBowl from '@/assets/Graphics/red.bowl.png'

const bowlOptions = [
  { name: 'Blue', src: blueBowl, color: '#60A5FA' },
  { name: 'Green', src: greenBowl, color: '#4ADE80' },
  { name: 'Orange', src: orangeBowl, color: '#FB923C' },
  { name: 'Red', src: redBowl, color: '#EF4444' },
]
const selectedBowl = ref(null)

// Current step: -1 = bowl pick, 0..4 = categories, 5 = serve
const stepIndex = ref(-1)

const multiSelectCategories = ['oil', 'protein', 'topping']

// Current category being picked (offset by 1 since step -1 is bowl)
const categoryIndex = computed(() => stepIndex.value)
const currentCategory = computed(() => ingredientStore.categories[categoryIndex.value] || null)
const currentIngredients = computed(() => {
  if (!currentCategory.value) return []
  return ingredientStore.ingredientsByCategory[currentCategory.value.name] || []
})
const isMultiSelect = computed(() =>
  currentCategory.value && multiSelectCategories.includes(currentCategory.value.name)
)
const isBowlStep = computed(() => stepIndex.value === -1)
const isLastStep = computed(() => stepIndex.value >= ingredientStore.categories.length)
const totalSteps = computed(() => ingredientStore.categories.length + 1) // +1 for bowl

// Step indicator text
const stepLabel = computed(() => {
  if (isBowlStep.value) return 'Bowl'
  if (isLastStep.value) return 'Ready to Serve!'
  return `${currentCategory.value?.displayName || ''}`
})

// Handle ingredient selection for current category
function handleSelect(ingredient) {
  const catName = currentCategory.value?.name
  if (!catName) return

  const current = [...bowlStore.selections[catName]]

  if (isMultiSelect.value) {
    const exists = current.includes(ingredient.id)
    const newIds = exists
      ? current.filter(id => id !== ingredient.id)
      : [...current, ingredient.id]
    updateCategory(catName, newIds)
  } else {
    // Single select — toggle on/off, no auto-advance
    const newIds = current.includes(ingredient.id) ? [] : [ingredient.id]
    updateCategory(catName, newIds)
  }
}

function updateCategory(catName, ids) {
  bowlStore.updateSelections({
    ...bowlStore.selections,
    [catName]: ids,
  })
}

function nextStep() {
  if (stepIndex.value < totalSteps.value - 1) {
    stepIndex.value++
  }
}

function prevStep() {
  if (stepIndex.value > 0) {
    stepIndex.value--
  }
}

function confirmMultiAndAdvance() {
  nextStep()
}

function serveBowl() {
  bowlStore.serveBowl()
    .then((result) => {
      console.log('Bowl served!', result)
    })
    .catch((err) => {
      console.error('Serve failed:', err)
    })
}

const showDetails = ref(false)
const favName = ref('')
const favSaved = ref(false)
const favSaving = ref(false)

function resetAndPlayAgain() {
  bowlStore.resetBowl()
  selectedBowl.value = null
  stepIndex.value = -1
  showDetails.value = false
  favName.value = ''
  favSaved.value = false
}

function pickBowl(bowl) {
  playSound('select', 0.2)
  selectedBowl.value = selectedBowl.value?.name === bowl.name ? null : bowl
}

function saveFavorite() {
  if (!favName.value.trim() || favSaving.value) return
  favSaving.value = true
  favoritesStore.save(favName.value.trim(), bowlStore.selectedIds)
    .then(() => {
      favSaved.value = true
    })
    .catch(() => {})
    .finally(() => {
      favSaving.value = false
    })
}

// Next rank XP threshold for the serve result XP bar
const serveNextRankXp = computed(() => {
  const xp = bowlStore.serveResult?.newTotalXp || 0
  const next = bowlStore.getNextRankThreshold(xp)
  return next?.minXp || 10000
})

// Pairings are already grouped by combo_name from the backend
const groupedPairings = computed(() => bowlStore.serveResult?.pairingsFound || [])

onMounted(() => {
  ingredientStore.loadAll()
})
</script>

<template>
  <div class="min-h-screen flex flex-col bg-ramen-darker">
    <NavBar />

    <!-- Achievement toast notification (fixed position, always visible) -->
    <AchievementToast
      v-if="newAchievements.length > 0"
      :achievements="newAchievements"
      @dismiss="bowlStore.serveResult.newAchievements = []"
    />

    <!-- Loading state -->
    <div v-if="ingredientStore.loading" class="flex-1 flex items-center justify-center">
      <PixelLoader text="Loading ingredients..." />
    </div>

    <!-- Error state -->
    <div v-else-if="ingredientStore.error" class="flex-1 flex items-center justify-center">
      <div class="font-pixel text-[10px] text-ramen-red">{{ ingredientStore.error }}</div>
    </div>

    <!-- Game layout -->
    <div
      v-else
      class="flex-1 flex flex-col items-center justify-end px-4 py-6 gap-4 bg-cover bg-center bg-no-repeat"
      :style="{ backgroundImage: `url(${restaurantBg})`, imageRendering: 'pixelated', backgroundColor: '#1a1018' }"
    >

      <!-- Serve result overlay (after serving) — horizontal layout -->
      <template v-if="bowlStore.serveResult">
        <div class="flex-1 flex items-center justify-center gap-8 px-4">
          <!-- Left: Bowl -->
          <div class="flex-shrink-0">
            <BowlBuilder :selections="bowlStore.selections" :ingredient-map="ingredientStore.ingredientMap" :bowl-sprite="selectedBowl?.src" />
          </div>

          <!-- Right: Stats panel -->
          <div class="bg-ramen-dark/95 border-2 border-ramen-gold/60 p-5 w-80 max-h-[80vh] overflow-y-auto space-y-3 shadow-lg shadow-ramen-gold/10 serve-panel">
            <h3 class="font-pixel text-sm text-ramen-gold text-center mb-1 animate-pulse">Bowl Served!</h3>

            <!-- Big total score -->
            <div class="text-center py-2">
              <div class="font-pixel text-3xl text-ramen-gold score-glow">{{ bowlStore.serveResult.totalScore }}</div>
              <div class="font-pixel text-[8px] text-ramen-cream/40 mt-1">TOTAL SCORE</div>
            </div>

            <!-- Score breakdown row -->
            <div class="flex justify-around text-center border-t border-b border-ramen-brown/50 py-2">
              <div>
                <div class="font-pixel text-sm text-ramen-orange">{{ bowlStore.serveResult.tastiness }}</div>
                <div class="font-pixel text-[6px] text-ramen-cream/40">TASTE</div>
              </div>
              <div class="w-px bg-ramen-brown/50"></div>
              <div>
                <div class="font-pixel text-sm text-ramen-neon">{{ bowlStore.serveResult.nutrition }}</div>
                <div class="font-pixel text-[6px] text-ramen-cream/40">NUTRITION</div>
              </div>
              <div class="w-px bg-ramen-brown/50"></div>
              <div>
                <div class="font-pixel text-sm text-ramen-gold">+{{ bowlStore.serveResult.xpEarned }}</div>
                <div class="font-pixel text-[6px] text-ramen-cream/40">XP EARNED</div>
              </div>
            </div>

            <!-- Details toggle -->
            <button
              class="font-pixel text-[8px] text-ramen-cream/40 hover:text-ramen-cream w-full text-center transition-colors"
              @click="showDetails = !showDetails"
            >
              {{ showDetails ? '- Hide Details' : '+ Show Details' }}
            </button>

            <!-- Detailed breakdown -->
            <div v-if="showDetails" class="space-y-2 border-t border-ramen-brown pt-2">
              <div>
                <div class="font-pixel text-[7px] text-ramen-orange mb-1">Tastiness</div>
                <div v-for="(item, i) in bowlStore.serveResult.tastinessBreakdown" :key="'t' + i"
                  class="flex justify-between font-pixel text-[7px] text-ramen-cream/60">
                  <span>{{ item.label }}</span>
                  <span :class="item.value >= 0 ? 'text-ramen-neon' : 'text-ramen-red'">{{ item.value >= 0 ? '+' : '' }}{{ item.value }}</span>
                </div>
              </div>
              <div>
                <div class="font-pixel text-[7px] text-ramen-neon mb-1">Nutrition</div>
                <div v-for="(item, i) in bowlStore.serveResult.nutritionBreakdown" :key="'n' + i"
                  class="flex justify-between font-pixel text-[7px] text-ramen-cream/60">
                  <span>{{ item.label }}</span>
                  <span :class="item.value > 0 ? 'text-ramen-neon' : 'text-ramen-cream/30'">+{{ item.value }}</span>
                </div>
              </div>
              <div v-if="bowlStore.serveResult.macros" class="grid grid-cols-4 gap-1 text-center border-t border-ramen-brown/50 pt-1">
                <div><div class="font-pixel text-[6px] text-ramen-cream/30">Cal</div><div class="font-pixel text-[7px] text-ramen-cream">{{ bowlStore.serveResult.macros.calories }}</div></div>
                <div><div class="font-pixel text-[6px] text-ramen-cream/30">Prot</div><div class="font-pixel text-[7px] text-ramen-cream">{{ bowlStore.serveResult.macros.protein }}g</div></div>
                <div><div class="font-pixel text-[6px] text-ramen-cream/30">Fat</div><div class="font-pixel text-[7px] text-ramen-cream">{{ bowlStore.serveResult.macros.fat }}g</div></div>
                <div><div class="font-pixel text-[6px] text-ramen-cream/30">Carbs</div><div class="font-pixel text-[7px] text-ramen-cream">{{ bowlStore.serveResult.macros.carbs }}g</div></div>
              </div>
            </div>

            <!-- Combos found -->
            <div v-if="groupedPairings.length > 0" class="border-t border-ramen-brown pt-2">
              <div class="font-pixel text-[7px] text-ramen-cream/40 mb-1">Combos:</div>
              <div v-for="p in groupedPairings" :key="p.combo_name" class="mb-1">
                <div class="font-pixel text-[7px]" :class="p.score_modifier >= 0 ? 'text-ramen-orange' : 'text-ramen-red'">
                  {{ p.combo_name }} ({{ p.score_modifier >= 0 ? '+' : '' }}{{ p.score_modifier }})
                </div>
                <div v-if="p.pairs && p.pairs.length" class="font-pixel text-[6px] text-ramen-cream/30 ml-2">
                  {{ [...new Set(p.pairs.flatMap(pr => pr.split(' + ')))].join(' + ') }}
                </div>
              </div>
            </div>

            <!-- Rank + XP -->
            <div class="border-t border-ramen-brown pt-2 space-y-1">
              <div class="text-center">
                <div class="font-pixel text-[7px] text-ramen-cream/40">Rank</div>
                <div class="font-pixel text-[10px] text-ramen-gold uppercase">{{ bowlStore.serveResult.newRank }}</div>
              </div>
              <XPBar :current-x-p="bowlStore.serveResult.newTotalXp" :max-x-p="serveNextRankXp" />
            </div>

            <!-- Save + Play Again -->
            <div class="border-t border-ramen-brown pt-2 space-y-2">
              <div v-if="favSaved" class="font-pixel text-[8px] text-ramen-neon text-center">Saved!</div>
              <div v-else class="flex gap-2 items-center">
                <input v-model="favName" type="text" placeholder="Name this bowl..."
                  class="flex-1 bg-ramen-darker border border-ramen-brown px-2 py-1 font-pixel text-[8px] text-ramen-cream placeholder-ramen-cream/30 outline-none focus:border-ramen-gold" />
                <PixelButton :label="favSaving ? '...' : 'SAVE'" variant="secondary" size="sm" :disabled="!favName.trim() || favSaving" @click="saveFavorite" />
              </div>
              <div class="flex justify-center">
                <PixelButton label="PLAY AGAIN" variant="primary" size="sm" @click="resetAndPlayAgain" />
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- Bowl building wizard -->
      <template v-else>
        <!-- Bowl -->
        <div class="flex-shrink-0">
          <BowlBuilder :selections="bowlStore.selections" :ingredient-map="ingredientStore.ingredientMap" :bowl-sprite="selectedBowl?.src" />
        </div>

        <!-- Step indicator (always visible) -->
        <div class="flex items-center gap-2 relative top-[5px]">
          <!-- Bowl dot -->
          <div
            class="w-2 h-2 rounded-full transition-colors cursor-pointer"
            :class="isBowlStep ? 'bg-ramen-orange' : 'bg-ramen-gold'"
            @click="stepIndex = -1"
          ></div>
          <!-- Category dots -->
          <div
            v-for="(cat, i) in ingredientStore.categories"
            :key="cat.id"
            class="w-2 h-2 rounded-full transition-colors cursor-pointer"
            :class="i < stepIndex ? 'bg-ramen-gold' : i === stepIndex ? 'bg-ramen-orange' : 'bg-ramen-brown'"
            @click="stepIndex = i"
          ></div>
        </div>

        <!-- Bowl color selection (step -1) -->
        <div v-if="isBowlStep" class="flex flex-col items-center gap-3 w-full max-w-4xl">
          <div class="flex items-center justify-between w-full">
            <div class="w-16"></div>
            <h3 class="font-pixel text-xs text-ramen-orange text-center">
              Bowl
              <span class="text-ramen-cream/40 text-[8px] block">pick one</span>
            </h3>
            <button
              class="font-pixel text-[8px] text-ramen-cream/40 hover:text-ramen-cream transition-colors w-16 py-3 -my-3 text-right"
              @click="stepIndex = 0"
            >
              SKIP &gt;
            </button>
          </div>
          <div class="flex gap-4 justify-center">
            <button
              v-for="bowl in bowlOptions"
              :key="bowl.name"
              class="flex flex-col items-center justify-center gap-1.5 p-2.5 border-2 transition-all w-[100px] h-[140px] flex-shrink-0"
              :class="selectedBowl?.name === bowl.name
                ? 'border-ramen-gold bg-ramen-dark shadow-[0_0_8px_rgba(255,215,0,0.3)]'
                : 'border-ramen-brown bg-ramen-darker hover:border-ramen-cream/40'"
              @click="pickBowl(bowl)"
            >
              <img :src="bowl.src" class="pixel-render" style="width: 64px; height: auto;" :alt="bowl.name" />
              <span class="font-pixel text-[8px] text-ramen-cream">{{ bowl.name }}</span>
            </button>
          </div>
        </div>

        <!-- Category picker -->
        <div v-else class="flex flex-col items-center gap-3 w-full max-w-4xl">

          <!-- Category picker (one at a time) -->
          <div class="w-full">
            <!-- Category header with back/skip -->
            <div class="flex items-center justify-between mb-3">
              <button
                v-if="stepIndex > 0"
                class="font-pixel text-[8px] text-ramen-cream/40 hover:text-ramen-cream transition-colors w-16 py-3 -my-3"
                @click="prevStep"
              >
                &lt; BACK
              </button>
              <button
                v-else
                class="font-pixel text-[8px] text-ramen-cream/40 hover:text-ramen-cream transition-colors w-16 py-3 -my-3"
                @click="stepIndex = -1"
              >
                &lt; BOWL
              </button>

              <h3 class="font-pixel text-xs text-ramen-orange text-center">
                {{ stepLabel }}
                <span v-if="isLastStep" class="text-ramen-cream/40 text-[8px] block">&nbsp;</span>
                <span v-else-if="isMultiSelect" class="text-ramen-cream/40 text-[8px] block">pick any</span>
                <span v-else class="text-ramen-cream/40 text-[8px] block">pick one</span>
              </h3>

              <button
                v-if="stepIndex < totalSteps - 1"
                class="font-pixel text-[8px] hover:text-ramen-cream transition-colors w-16 text-right py-3 -my-3"
                :class="isMultiSelect && bowlStore.selections[currentCategory.name]?.length > 0
                  ? 'text-ramen-orange'
                  : 'text-ramen-cream/40'"
                @click="nextStep"
              >
                {{ isMultiSelect && bowlStore.selections[currentCategory.name]?.length > 0 ? 'NEXT' : 'SKIP' }} &gt;
              </button>
              <div v-else class="w-16"></div>
            </div>

            <!-- Ingredient row (always single line) -->
            <div class="flex gap-2 justify-center flex-nowrap">
              <IngredientCard
                v-for="ingredient in currentIngredients"
                :key="ingredient.id"
                :ingredient="ingredient"
                :selected="bowlStore.selections[currentCategory.name]?.includes(ingredient.id)"
                @select="handleSelect"
              />
            </div>

          </div>
        </div>

        <!-- Bottom: Serve button (always visible) -->
        <div class="flex-shrink-0 flex flex-col items-center gap-1 pb-2">
          <PixelButton
            :label="bowlStore.serving ? 'SERVING...' : 'SERVE BOWL'"
            variant="primary"
            size="lg"
            :disabled="!bowlStore.hasMinimumBowl || bowlStore.serving"
            @click="serveBowl"
          />
          <p v-if="bowlStore.serveError" class="font-pixel text-[8px] text-ramen-red">
            {{ bowlStore.serveError }}
          </p>
        </div>
      </template>
    </div>
  </div>
</template>
