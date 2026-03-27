<script setup>
/**
 * PlayPage — Main game screen
 *
 * Uses Pinia stores for all state management:
 * - useIngredientStore: categories, ingredients (loaded once, cached)
 * - useBowlStore: current bowl selections, scoring, serve action
 */
import { onMounted, computed } from 'vue'
import NavBar from '@/components/organisms/NavBar/NavBar.vue'
import BowlBuilder from '@/components/organisms/BowlBuilder/BowlBuilder.vue'
import IngredientPanel from '@/components/organisms/IngredientPanel/IngredientPanel.vue'
import PixelButton from '@/components/atoms/PixelButton/PixelButton.vue'
import XPBar from '@/components/atoms/XPBar/XPBar.vue'
import { useIngredientStore } from '@/stores/ingredients'
import { useBowlStore } from '@/stores/bowl'

const ingredientStore = useIngredientStore()
const bowlStore = useBowlStore()

function onSelectionsUpdate(newSelections) {
  bowlStore.updateSelections(newSelections)
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

function resetAndPlayAgain() {
  bowlStore.resetBowl()
}

// Next rank XP threshold for the serve result XP bar
const serveNextRankXp = computed(() => {
  const xp = bowlStore.serveResult?.newTotalXp || 0
  const next = bowlStore.getNextRankThreshold(xp)
  return next?.minXp || 10000
})

// Group pairings by combo_name so "Classic Tonkotsu (+10)" and "(+5)" show as one line
const groupedPairings = computed(() => {
  const pairings = bowlStore.serveResult?.pairingsFound || []
  const grouped = {}
  pairings.forEach((p) => {
    const name = p.combo_name
    if (!grouped[name]) {
      grouped[name] = { combo_name: name, total_modifier: 0, descriptions: [] }
    }
    grouped[name].total_modifier += p.score_modifier
    if (p.description) grouped[name].descriptions.push(p.description)
  })
  return Object.values(grouped)
})

onMounted(() => {
  ingredientStore.loadAll()
})
</script>

<template>
  <div class="min-h-screen bg-ramen-darker">
    <NavBar />

    <div class="max-w-6xl mx-auto p-4">
      <h1 class="font-pixel text-lg text-ramen-orange mb-4">Build Your Bowl</h1>

      <!-- Loading state -->
      <div v-if="ingredientStore.loading" class="font-pixel text-[10px] text-ramen-cream/50 text-center py-16">
        Loading ingredients...
      </div>

      <!-- Error state -->
      <div v-else-if="ingredientStore.error" class="font-pixel text-[10px] text-ramen-red text-center py-16">
        {{ ingredientStore.error }}
      </div>

      <!-- Loaded: game layout -->
      <div v-else class="flex flex-col lg:flex-row gap-6">

        <!-- Left: Bowl visualization + serve controls -->
        <div class="flex flex-col items-center gap-4">
          <BowlBuilder :selections="bowlStore.selections" :ingredient-map="ingredientStore.ingredientMap" />

          <!-- Serve result panel -->
          <div v-if="bowlStore.serveResult" class="bg-ramen-dark border border-ramen-neon p-4 w-full max-w-xs space-y-2">
            <h3 class="font-pixel text-xs text-ramen-neon text-center">Bowl Served!</h3>
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

            <!-- Pairings found (grouped by combo name) -->
            <div v-if="groupedPairings.length > 0" class="border-t border-ramen-brown pt-2">
              <div class="font-pixel text-[8px] text-ramen-cream/40 mb-1">Combos Found:</div>
              <div v-for="p in groupedPairings" :key="p.combo_name" class="font-pixel text-[8px] text-ramen-orange">
                {{ p.combo_name }} ({{ p.total_modifier >= 0 ? '+' : '' }}{{ p.total_modifier }})
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

            <PixelButton label="PLAY AGAIN" variant="primary" size="md" @click="resetAndPlayAgain" />
          </div>

          <!-- Serve button (shown when bowl not yet served) -->
          <template v-else>
            <PixelButton
              :label="bowlStore.serving ? 'SERVING...' : 'SERVE BOWL'"
              variant="primary"
              size="lg"
              :disabled="!bowlStore.hasMinimumBowl || bowlStore.serving"
              @click="serveBowl"
            />
            <p v-if="!bowlStore.hasMinimumBowl && !bowlStore.isEmpty" class="font-pixel text-[8px] text-ramen-cream/40 text-center">
              Add at least a broth and noodles
            </p>
            <p v-if="bowlStore.serveError" class="font-pixel text-[8px] text-ramen-red text-center">
              {{ bowlStore.serveError }}
            </p>
          </template>
        </div>

        <!-- Right: Ingredient picker -->
        <div class="flex-1 min-w-0">
          <IngredientPanel
            :categories="ingredientStore.categories"
            :ingredients-by-category="ingredientStore.ingredientsByCategory"
            @update:selections="onSelectionsUpdate"
          />
        </div>
      </div>
    </div>
  </div>
</template>
