<script setup>
import { ref, onMounted } from 'vue'
import NavBar from '@/components/organisms/NavBar/NavBar.vue'
import {
  fetchPairings,
  fetchIngredients,
  createPairing,
  updatePairing,
  deletePairing,
} from '@/services/api'

const pairings = ref([])
const allIngredients = ref([])
const total = ref(0)
const page = ref(1)
const limit = 20
const loading = ref(false)
const error = ref('')

const showModal = ref(false)
const editing = ref(null)
const form = ref(getEmptyForm())

function getEmptyForm() {
  return { ingredient1Id: '', ingredient2Id: '', scoreModifier: 0, comboName: '', description: '' }
}

const showDeleteConfirm = ref(false)
const deletingItem = ref(null)

async function loadData() {
  loading.value = true; error.value = ''
  try {
    const result = await fetchPairings({ page: page.value, limit })
    pairings.value = result.data; total.value = result.total
  } catch (e) { error.value = e.response?.data?.error || 'Failed to load' }
  finally { loading.value = false }
}

async function loadIngredients() {
  try { const r = await fetchIngredients({ limit: 50 }); allIngredients.value = r.data } catch (e) { /* */ }
}

onMounted(() => { loadData(); loadIngredients() })

function openCreate() { editing.value = null; form.value = getEmptyForm(); showModal.value = true }

function openEdit(item) {
  editing.value = item
  form.value = {
    ingredient1Id: item.ingredient1Id, ingredient2Id: item.ingredient2Id,
    scoreModifier: item.scoreModifier, comboName: item.comboName || '', description: item.description || '',
  }
  showModal.value = true
}

async function handleSave() {
  error.value = ''
  try {
    const body = {
      ...form.value,
      ingredient1Id: Number(form.value.ingredient1Id),
      ingredient2Id: Number(form.value.ingredient2Id),
      scoreModifier: Number(form.value.scoreModifier),
    }
    if (editing.value) { await updatePairing(editing.value.id, body) }
    else { await createPairing(body) }
    showModal.value = false; await loadData()
  } catch (e) { error.value = e.response?.data?.error || 'Save failed' }
}

function confirmDelete(item) { deletingItem.value = item; showDeleteConfirm.value = true }

async function handleDelete() {
  error.value = ''
  try {
    await deletePairing(deletingItem.value.id)
    showDeleteConfirm.value = false; deletingItem.value = null; await loadData()
  } catch (e) { error.value = e.response?.data?.error || 'Delete failed' }
}

function prevPage() { if (page.value > 1) { page.value--; loadData() } }
function nextPage() { if (page.value * limit < total.value) { page.value++; loadData() } }

function scoreColor(score) {
  if (score >= 15) return 'text-ramen-gold'
  if (score > 0) return 'text-green-400'
  if (score < -10) return 'text-ramen-red'
  if (score < 0) return 'text-orange-400'
  return 'text-ramen-cream/40'
}

function scoreLabel(score) {
  if (score >= 15) return '最高' // Best
  if (score >= 5) return '良い' // Good
  if (score <= -10) return '最悪' // Worst
  if (score < 0) return '悪い' // Bad
  return '普通' // Normal
}
</script>

<template>
  <div class="min-h-screen bg-ramen-darker">
    <NavBar />
    <div class="max-w-5xl mx-auto p-6">

      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center gap-3 mb-1">
          <span class="text-2xl">⚡</span>
          <h1 class="font-pixel text-xl text-ramen-orange">Pairings</h1>
          <span class="font-pixel text-xs text-ramen-cream/30">相性管理</span>
        </div>
        <div class="flex items-center gap-2 mb-4">
          <div class="h-px flex-1 bg-gradient-to-r from-ramen-orange/60 via-ramen-brown to-transparent"></div>
          <span class="text-ramen-cream/20 text-xs">◆</span>
          <div class="h-px flex-1 bg-gradient-to-l from-ramen-orange/60 via-ramen-brown to-transparent"></div>
        </div>
        <div class="flex items-center justify-between">
          <router-link to="/admin" class="text-ramen-neon text-sm hover:underline">&larr; Back to Kitchen</router-link>
          <button @click="openCreate"
            class="font-pixel text-xs bg-ramen-red text-ramen-cream px-4 py-2 hover:bg-ramen-orange transition-colors pixel-border">
            + New Pairing
          </button>
        </div>
      </div>

      <div v-if="error" class="bg-ramen-red/20 border-l-4 border-ramen-red text-ramen-cream text-sm px-4 py-3 mb-6">
        {{ error }}
      </div>

      <div v-if="loading" class="text-center py-12">
        <div class="font-pixel text-sm text-ramen-orange animate-pulse">Loading pairings...</div>
        <div class="text-ramen-cream/30 text-xs mt-2">お待ちください</div>
      </div>

      <!-- Pairing cards -->
      <div v-else class="space-y-3">
        <div v-for="item in pairings" :key="item.id"
          class="bg-ramen-dark border border-ramen-brown/50 p-4 group hover:border-ramen-brown transition-colors">

          <div class="flex items-center gap-4">
            <!-- Ingredient 1 -->
            <div class="flex-1 text-right">
              <span class="font-pixel text-xs text-ramen-cream">{{ item.ingredient1Name }}</span>
            </div>

            <!-- Score connector -->
            <div class="flex flex-col items-center min-w-[80px]">
              <div class="flex items-center gap-1">
                <span class="text-ramen-cream/20">━━</span>
                <span :class="scoreColor(item.scoreModifier)" class="font-pixel text-sm font-bold">
                  {{ item.scoreModifier > 0 ? '+' : '' }}{{ item.scoreModifier }}
                </span>
                <span class="text-ramen-cream/20">━━</span>
              </div>
              <span :class="scoreColor(item.scoreModifier)" class="text-xs mt-0.5">
                {{ scoreLabel(item.scoreModifier) }}
              </span>
            </div>

            <!-- Ingredient 2 -->
            <div class="flex-1">
              <span class="font-pixel text-xs text-ramen-cream">{{ item.ingredient2Name }}</span>
            </div>

            <!-- Combo name -->
            <div class="hidden sm:block min-w-[120px]">
              <span v-if="item.comboName" class="font-pixel text-xs text-ramen-gold/60">
                「{{ item.comboName }}」
              </span>
            </div>

            <!-- Actions -->
            <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
              <button @click="openEdit(item)" class="font-pixel text-xs text-ramen-neon hover:text-ramen-gold">Edit</button>
              <button @click="confirmDelete(item)" class="font-pixel text-xs text-ramen-red/60 hover:text-ramen-red">Del</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div class="flex items-center justify-between mt-8 pt-4 border-t border-ramen-brown/30">
        <span class="text-ramen-cream/40 text-xs">{{ total }} pairings configured</span>
        <div class="flex items-center gap-3">
          <button @click="prevPage" :disabled="page <= 1"
            class="font-pixel text-xs text-ramen-cream disabled:text-ramen-cream/20 hover:text-ramen-orange">◄ Prev</button>
          <span class="font-pixel text-xs text-ramen-gold">{{ page }}</span>
          <button @click="nextPage" :disabled="page * limit >= total"
            class="font-pixel text-xs text-ramen-cream disabled:text-ramen-cream/20 hover:text-ramen-orange">Next ►</button>
        </div>
      </div>

      <!-- Create/Edit Modal -->
      <div v-if="showModal" class="fixed inset-0 bg-black/80 flex items-center justify-center z-50 p-4">
        <div class="bg-ramen-dark border-2 border-ramen-brown w-full max-w-md p-6 pixel-border">
          <div class="flex items-center gap-2 mb-4">
            <span class="text-lg">{{ editing ? '✏️' : '⚡' }}</span>
            <h2 class="font-pixel text-sm text-ramen-orange">
              {{ editing ? 'Edit Pairing' : 'New Pairing' }}
            </h2>
          </div>
          <form @submit.prevent="handleSave" class="space-y-3">
            <div>
              <label class="block font-pixel text-xs text-ramen-cream/60 mb-1">Ingredient 1 *</label>
              <select v-model="form.ingredient1Id" required
                class="w-full bg-ramen-darker border border-ramen-brown text-ramen-cream px-3 py-2 text-sm focus:border-ramen-orange focus:outline-none">
                <option value="" disabled>Select ingredient</option>
                <option v-for="ing in allIngredients" :key="ing.id" :value="ing.id">{{ ing.name }}</option>
              </select>
            </div>
            <div>
              <label class="block font-pixel text-xs text-ramen-cream/60 mb-1">Ingredient 2 *</label>
              <select v-model="form.ingredient2Id" required
                class="w-full bg-ramen-darker border border-ramen-brown text-ramen-cream px-3 py-2 text-sm focus:border-ramen-orange focus:outline-none">
                <option value="" disabled>Select ingredient</option>
                <option v-for="ing in allIngredients" :key="ing.id" :value="ing.id">{{ ing.name }}</option>
              </select>
            </div>
            <div>
              <label class="block font-pixel text-xs text-ramen-cream/60 mb-1">Score Modifier *</label>
              <input v-model="form.scoreModifier" type="number" required
                class="w-full bg-ramen-darker border border-ramen-brown text-ramen-cream px-3 py-2 text-sm focus:border-ramen-orange focus:outline-none" />
              <p class="text-xs text-ramen-cream/30 mt-1">Positive = good combo, Negative = bad combo</p>
            </div>
            <div>
              <label class="block font-pixel text-xs text-ramen-cream/60 mb-1">Combo Name</label>
              <input v-model="form.comboName" placeholder="e.g. Classic Tonkotsu"
                class="w-full bg-ramen-darker border border-ramen-brown text-ramen-cream px-3 py-2 text-sm focus:border-ramen-orange focus:outline-none" />
            </div>
            <div>
              <label class="block font-pixel text-xs text-ramen-cream/60 mb-1">Description</label>
              <textarea v-model="form.description" rows="2"
                class="w-full bg-ramen-darker border border-ramen-brown text-ramen-cream px-3 py-2 text-sm focus:border-ramen-orange focus:outline-none" />
            </div>
            <div class="flex gap-3 pt-3 border-t border-ramen-brown/30">
              <button type="submit"
                class="font-pixel text-xs bg-ramen-red text-ramen-cream px-4 py-2 hover:bg-ramen-orange transition-colors">
                {{ editing ? 'Save' : 'Create' }}
              </button>
              <button type="button" @click="showModal = false"
                class="font-pixel text-xs text-ramen-cream/40 hover:text-ramen-cream px-4 py-2">Cancel</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Delete Confirmation -->
      <div v-if="showDeleteConfirm" class="fixed inset-0 bg-black/80 flex items-center justify-center z-50 p-4">
        <div class="bg-ramen-dark border-2 border-ramen-red w-full max-w-sm p-6 text-center">
          <div class="text-3xl mb-3">⚠️</div>
          <p class="font-pixel text-sm text-ramen-cream mb-1">Remove pairing?</p>
          <p class="text-sm text-ramen-cream/40 mb-1">{{ deletingItem?.ingredient1Name }} ✕ {{ deletingItem?.ingredient2Name }}</p>
          <p class="font-pixel text-xs text-ramen-red/60 mb-4">この操作は取り消せません</p>
          <div class="flex justify-center gap-3">
            <button @click="handleDelete"
              class="font-pixel text-xs bg-ramen-red text-ramen-cream px-4 py-2 hover:bg-red-700">Delete</button>
            <button @click="showDeleteConfirm = false"
              class="font-pixel text-xs text-ramen-cream/40 hover:text-ramen-cream px-4 py-2">Cancel</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
