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
import { useIngredientStore } from '@/stores/ingredients'
import { useBowlStore } from '@/stores/bowl'

const ingredientStore = useIngredientStore()
const bowlStore = useBowlStore()

// Achievements unlocked from the latest serve (read from serve result)
const newAchievements = computed(() => bowlStore.serveResult?.newAchievements || [])

// Current step index (0 = broth, 1 = noodles, ... 4 = topping, 5 = ready to serve)
const stepIndex = ref(0)

const multiSelectCategories = ['oil', 'protein', 'topping']

// Current category being picked
const currentCategory = computed(() => ingredientStore.categories[stepIndex.value] || null)
const currentIngredients = computed(() => {
  if (!currentCategory.value) return []
  return ingredientStore.ingredientsByCategory[currentCategory.value.name] || []
})
const isMultiSelect = computed(() =>
  currentCategory.value && multiSelectCategories.includes(currentCategory.value.name)
)
const isLastStep = computed(() => stepIndex.value >= ingredientStore.categories.length)
const totalSteps = computed(() => ingredientStore.categories.length)

// Step indicator text
const stepLabel = computed(() => {
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
    // Single select — pick and auto-advance
    const newIds = current.includes(ingredient.id) ? [] : [ingredient.id]
    updateCategory(catName, newIds)
    // Auto-advance for single select after a short delay
    if (newIds.length > 0) {
      setTimeout(() => nextStep(), 300)
    }
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

function resetAndPlayAgain() {
  bowlStore.resetBowl()
  stepIndex.value = 0
  showDetails.value = false
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
  <div class="min-h-screen bg-ramen-darker flex flex-col">
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
    <div v-else class="flex-1 flex flex-col items-center justify-end px-4 py-6 gap-4">

      <!-- Serve result overlay (after serving) -->
      <template v-if="bowlStore.serveResult">
        <div class="flex-1 flex flex-col items-center justify-center gap-4">
          <BowlBuilder :selections="bowlStore.selections" :ingredient-map="ingredientStore.ingredientMap" />

          <div class="bg-ramen-dark border border-ramen-neon p-4 w-full max-w-sm space-y-2">
            <h3 class="font-pixel text-xs text-ramen-neon text-center">Bowl Served!</h3>

            <!-- Score summary -->
            <div class="grid grid-cols-2 gap-2 text-center">
              <div>
                <div class="font-pixel text-[8px] text-ramen-cream/40">Tastiness</div>
                <div class="font-pixel text-sm text-ramen-orange">{{ bowlStore.serveResult.tastiness }}</div>
              </div>
              <div>
                <div class="font-pixel text-[8px] text-ramen-cream/40">Nutrition</div>
                <div class="font-pixel text-sm text-ramen-neon">{{ bowlStore.serveResult.nutrition }}</div>
              </div>
              <div>
                <div class="font-pixel text-[8px] text-ramen-cream/40">Total</div>
                <div class="font-pixel text-sm text-ramen-gold">{{ bowlStore.serveResult.totalScore }}</div>
              </div>
              <div>
                <div class="font-pixel text-[8px] text-ramen-cream/40">XP Earned</div>
                <div class="font-pixel text-sm text-ramen-gold">+{{ bowlStore.serveResult.xpEarned }}</div>
              </div>
            </div>

            <!-- Details toggle -->
            <button
              class="font-pixel text-[8px] text-ramen-cream/40 hover:text-ramen-cream w-full text-center pt-1 transition-colors"
              @click="showDetails = !showDetails"
            >
              {{ showDetails ? '- Hide Details' : '+ Show Details' }}
            </button>

            <!-- Detailed breakdown -->
            <div v-if="showDetails" class="space-y-2 border-t border-ramen-brown pt-2">
              <!-- Tastiness breakdown -->
              <div>
                <div class="font-pixel text-[8px] text-ramen-orange mb-1">Tastiness Breakdown</div>
                <div
                  v-for="(item, i) in bowlStore.serveResult.tastinessBreakdown"
                  :key="'t' + i"
                  class="flex justify-between font-pixel text-[7px] text-ramen-cream/60"
                >
                  <span>{{ item.label }}</span>
                  <span :class="item.value >= 0 ? 'text-ramen-neon' : 'text-ramen-red'">
                    {{ item.value >= 0 ? '+' : '' }}{{ item.value }}
                  </span>
                </div>
              </div>

              <!-- Nutrition breakdown -->
              <div>
                <div class="font-pixel text-[8px] text-ramen-neon mb-1">Nutrition Breakdown</div>
                <div
                  v-for="(item, i) in bowlStore.serveResult.nutritionBreakdown"
                  :key="'n' + i"
                  class="flex justify-between font-pixel text-[7px] text-ramen-cream/60"
                >
                  <span>{{ item.label }}</span>
                  <span :class="item.value > 0 ? 'text-ramen-neon' : 'text-ramen-cream/30'">
                    +{{ item.value }}
                  </span>
                </div>
              </div>

              <!-- Macros from real API data -->
              <div v-if="bowlStore.serveResult.macros" class="border-t border-ramen-brown/50 pt-1">
                <div class="font-pixel text-[8px] text-ramen-cream/40 mb-1">Actual Macros</div>
                <div class="grid grid-cols-4 gap-1 text-center">
                  <div>
                    <div class="font-pixel text-[7px] text-ramen-cream/30">Cal</div>
                    <div class="font-pixel text-[8px] text-ramen-cream">{{ bowlStore.serveResult.macros.calories }}</div>
                  </div>
                  <div>
                    <div class="font-pixel text-[7px] text-ramen-cream/30">Prot</div>
                    <div class="font-pixel text-[8px] text-ramen-cream">{{ bowlStore.serveResult.macros.protein }}g</div>
                  </div>
                  <div>
                    <div class="font-pixel text-[7px] text-ramen-cream/30">Fat</div>
                    <div class="font-pixel text-[8px] text-ramen-cream">{{ bowlStore.serveResult.macros.fat }}g</div>
                  </div>
                  <div>
                    <div class="font-pixel text-[7px] text-ramen-cream/30">Carbs</div>
                    <div class="font-pixel text-[8px] text-ramen-cream">{{ bowlStore.serveResult.macros.carbs }}g</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Combos found -->
            <div v-if="groupedPairings.length > 0" class="border-t border-ramen-brown pt-2">
              <div class="font-pixel text-[8px] text-ramen-cream/40 mb-1">Combos Found:</div>
              <div v-for="p in groupedPairings" :key="p.combo_name" class="font-pixel text-[8px] text-ramen-orange">
                {{ p.combo_name }} ({{ p.score_modifier >= 0 ? '+' : '' }}{{ p.score_modifier }})
              </div>
            </div>

            <!-- Rank + XP progression -->
            <div class="border-t border-ramen-brown pt-2 space-y-2">
              <div class="text-center">
                <div class="font-pixel text-[8px] text-ramen-cream/40">Rank</div>
                <div class="font-pixel text-xs text-ramen-gold uppercase">{{ bowlStore.serveResult.newRank }}</div>
              </div>
              <XPBar
                :current-x-p="bowlStore.serveResult.newTotalXp"
                :max-x-p="serveNextRankXp"
              />
            </div>

            <div class="flex justify-center pt-1">
              <PixelButton label="PLAY AGAIN" variant="primary" size="md" @click="resetAndPlayAgain" />
            </div>
          </div>
        </div>
      </template>

      <!-- Bowl building wizard -->
      <template v-else>
        <!-- Bowl -->
        <div class="flex-shrink-0">
          <BowlBuilder :selections="bowlStore.selections" :ingredient-map="ingredientStore.ingredientMap" />
        </div>

        <!-- Middle: Step dots + category picker -->
        <div class="flex flex-col items-center gap-3 w-full max-w-4xl">
          <!-- Step indicator -->
          <div class="flex items-center gap-2">
            <div
              v-for="(cat, i) in ingredientStore.categories"
              :key="cat.id"
              class="w-2 h-2 rounded-full transition-colors cursor-pointer"
              :class="i < stepIndex ? 'bg-ramen-gold' : i === stepIndex ? 'bg-ramen-orange' : 'bg-ramen-brown'"
              @click="stepIndex = i"
            ></div>
          </div>

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
              <div v-else class="w-16"></div>

              <h3 class="font-pixel text-xs text-ramen-orange text-center">
                {{ stepLabel }}
                <span v-if="isMultiSelect" class="text-ramen-cream/40 text-[8px] block">pick any, then confirm</span>
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
          <p v-if="!bowlStore.hasMinimumBowl && bowlStore.totalIngredients > 0" class="font-pixel text-[8px] text-ramen-cream/40">
            Add at least a broth and noodles
          </p>
          <p v-if="bowlStore.serveError" class="font-pixel text-[8px] text-ramen-red">
            {{ bowlStore.serveError }}
          </p>
        </div>
      </template>
    </div>
  </div>
</template>
