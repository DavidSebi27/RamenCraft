<script setup>
import { ref, computed, onMounted } from 'vue'
import NavBar from '@/components/organisms/NavBar/NavBar.vue'
import {
  fetchIngredients,
  fetchCategories,
  createIngredient,
  updateIngredient,
  deleteIngredient,
} from '@/services/api'

const ingredients = ref([])
const categories = ref([])
const total = ref(0)
const page = ref(1)
const limit = 20
const loading = ref(false)
const error = ref('')
const filterCategory = ref('')

const showModal = ref(false)
const editing = ref(null)
const form = ref(getEmptyForm())

function getEmptyForm() {
  return {
    name: '', nameJp: '', categoryId: '', description: '',
    caloriesPerServing: '', proteinG: '', fatG: '', carbsG: '',
  }
}

const showDeleteConfirm = ref(false)
const deletingItem = ref(null)

// Category visual mapping
const categoryStyle = {
  broth: { bg: 'bg-amber-900/40', border: 'border-amber-600', label: 'スープ', emoji: '🍜' },
  noodle: { bg: 'bg-yellow-900/40', border: 'border-yellow-600', label: '麺', emoji: '🍝' },
  protein: { bg: 'bg-red-900/40', border: 'border-red-600', label: 'タンパク質', emoji: '🥩' },
  topping: { bg: 'bg-green-900/40', border: 'border-green-600', label: 'トッピング', emoji: '🥬' },
  oil: { bg: 'bg-orange-900/40', border: 'border-orange-500', label: '油', emoji: '🫒' },
}

function getCatStyle(name) {
  return categoryStyle[name?.toLowerCase()] || { bg: 'bg-ramen-dark', border: 'border-ramen-brown', label: name, emoji: '🍱' }
}

const filteredIngredients = computed(() => {
  if (!filterCategory.value) return ingredients.value
  return ingredients.value.filter(i => i.categoryName?.toLowerCase() === filterCategory.value)
})

async function loadData() {
  loading.value = true
  error.value = ''
  try {
    const result = await fetchIngredients({ page: page.value, limit })
    ingredients.value = result.data
    total.value = result.total
  } catch (e) {
    error.value = e.response?.data?.error || 'Failed to load ingredients'
  } finally {
    loading.value = false
  }
}

async function loadCategories() {
  try {
    const result = await fetchCategories()
    categories.value = Array.isArray(result) ? result : (result.data || [])
  } catch (e) { /* */ }
}

onMounted(() => { loadData(); loadCategories() })

function openCreate() {
  editing.value = null
  form.value = getEmptyForm()
  showModal.value = true
}

function openEdit(item) {
  editing.value = item
  form.value = {
    name: item.name, nameJp: item.nameJp || '', categoryId: item.categoryId,
    description: item.description || '', caloriesPerServing: item.caloriesPerServing ?? '',
    proteinG: item.proteinG ?? '', fatG: item.fatG ?? '', carbsG: item.carbsG ?? '',
  }
  showModal.value = true
}

async function handleSave() {
  error.value = ''
  try {
    const body = { ...form.value }
    for (const key of ['caloriesPerServing', 'proteinG', 'fatG', 'carbsG']) {
      body[key] = body[key] === '' ? null : Number(body[key])
    }
    body.categoryId = Number(body.categoryId)
    if (editing.value) { await updateIngredient(editing.value.id, body) }
    else { await createIngredient(body) }
    showModal.value = false
    await loadData()
  } catch (e) { error.value = e.response?.data?.error || 'Save failed' }
}

function confirmDelete(item) { deletingItem.value = item; showDeleteConfirm.value = true }

async function handleDelete() {
  error.value = ''
  try {
    await deleteIngredient(deletingItem.value.id)
    showDeleteConfirm.value = false; deletingItem.value = null
    await loadData()
  } catch (e) { error.value = e.response?.data?.error || 'Delete failed' }
}

function prevPage() { if (page.value > 1) { page.value--; loadData() } }
function nextPage() { if (page.value * limit < total.value) { page.value++; loadData() } }
</script>

<template>
  <div class="min-h-screen bg-ramen-darker">
    <NavBar />
    <div class="max-w-5xl mx-auto p-6">

      <!-- Header with Japanese decorative line -->
      <div class="mb-8">
        <div class="flex items-center gap-3 mb-1">
          <span class="text-2xl">🍜</span>
          <h1 class="font-pixel text-xl text-ramen-orange">Ingredients</h1>
          <span class="font-pixel text-xs text-ramen-cream/30">材料管理</span>
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
            + New Ingredient
          </button>
        </div>
      </div>

      <div v-if="error" class="bg-ramen-red/20 border-l-4 border-ramen-red text-ramen-cream text-sm px-4 py-3 mb-6">
        {{ error }}
      </div>

      <!-- Category filter tabs -->
      <div class="flex flex-wrap gap-2 mb-6">
        <button @click="filterCategory = ''"
          :class="!filterCategory ? 'bg-ramen-orange text-ramen-darker' : 'bg-ramen-dark text-ramen-cream/60 hover:text-ramen-cream'"
          class="font-pixel text-xs px-3 py-1.5 transition-colors">
          All
        </button>
        <button v-for="cat in categories" :key="cat.id"
          @click="filterCategory = cat.name.toLowerCase()"
          :class="filterCategory === cat.name.toLowerCase()
            ? getCatStyle(cat.name).bg + ' text-ramen-cream border ' + getCatStyle(cat.name).border
            : 'bg-ramen-dark text-ramen-cream/60 hover:text-ramen-cream border border-ramen-brown/30'"
          class="font-pixel text-xs px-3 py-1.5 transition-colors">
          {{ getCatStyle(cat.name).emoji }} {{ cat.name }}
        </button>
      </div>

      <div v-if="loading" class="text-center py-12">
        <div class="font-pixel text-sm text-ramen-orange animate-pulse">Loading ingredients...</div>
        <div class="text-ramen-cream/30 text-xs mt-2">お待ちください</div>
      </div>

      <!-- Ingredient cards grid -->
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        <div v-for="item in filteredIngredients" :key="item.id"
          :class="[getCatStyle(item.categoryName).bg, getCatStyle(item.categoryName).border]"
          class="border p-4 relative group hover:scale-[1.02] transition-transform">

          <!-- Category badge -->
          <div class="flex items-center justify-between mb-2">
            <span class="font-pixel text-xs text-ramen-cream/40">
              {{ getCatStyle(item.categoryName).emoji }} {{ getCatStyle(item.categoryName).label }}
            </span>
            <span class="text-ramen-cream/20 text-xs">#{{ item.id }}</span>
          </div>

          <!-- Name -->
          <h3 class="font-pixel text-xs text-ramen-cream mb-1">{{ item.name }}</h3>
          <p v-if="item.nameJp" class="text-ramen-gold/60 text-xs mb-2">{{ item.nameJp }}</p>

          <!-- Nutrition bar -->
          <div v-if="item.caloriesPerServing" class="flex gap-3 text-xs text-ramen-cream/40 mb-3">
            <span>{{ item.caloriesPerServing }} cal</span>
            <span v-if="item.proteinG">P:{{ item.proteinG }}g</span>
            <span v-if="item.fatG">F:{{ item.fatG }}g</span>
            <span v-if="item.carbsG">C:{{ item.carbsG }}g</span>
          </div>

          <!-- Actions (appear on hover) -->
          <div class="flex gap-2 mt-2 opacity-0 group-hover:opacity-100 transition-opacity">
            <button @click="openEdit(item)"
              class="font-pixel text-xs text-ramen-neon hover:text-ramen-gold transition-colors">
              Edit
            </button>
            <span class="text-ramen-brown">|</span>
            <button @click="confirmDelete(item)"
              class="font-pixel text-xs text-ramen-red/60 hover:text-ramen-red transition-colors">
              Delete
            </button>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div class="flex items-center justify-between mt-8 pt-4 border-t border-ramen-brown/30">
        <span class="text-ramen-cream/40 text-xs">{{ total }} ingredients in stock</span>
        <div class="flex items-center gap-3">
          <button @click="prevPage" :disabled="page <= 1"
            class="font-pixel text-xs text-ramen-cream disabled:text-ramen-cream/20 hover:text-ramen-orange transition-colors">
            ◄ Prev
          </button>
          <span class="font-pixel text-xs text-ramen-gold">{{ page }}</span>
          <button @click="nextPage" :disabled="page * limit >= total"
            class="font-pixel text-xs text-ramen-cream disabled:text-ramen-cream/20 hover:text-ramen-orange transition-colors">
            Next ►
          </button>
        </div>
      </div>

      <!-- Create/Edit Modal -->
      <div v-if="showModal" class="fixed inset-0 bg-black/80 flex items-center justify-center z-50 p-4">
        <div class="bg-ramen-dark border-2 border-ramen-brown w-full max-w-md p-6 pixel-border">
          <div class="flex items-center gap-2 mb-4">
            <span class="text-lg">{{ editing ? '✏️' : '✨' }}</span>
            <h2 class="font-pixel text-sm text-ramen-orange">
              {{ editing ? 'Edit Ingredient' : 'New Ingredient' }}
            </h2>
            <span class="font-pixel text-xs text-ramen-cream/20">{{ editing ? '編集' : '新規' }}</span>
          </div>

          <form @submit.prevent="handleSave" class="space-y-3">
            <div class="grid grid-cols-2 gap-3">
              <div class="col-span-2 sm:col-span-1">
                <label class="block font-pixel text-xs text-ramen-cream/60 mb-1">Name *</label>
                <input v-model="form.name" required
                  class="w-full bg-ramen-darker border border-ramen-brown text-ramen-cream px-3 py-2 text-sm focus:border-ramen-orange focus:outline-none" />
              </div>
              <div class="col-span-2 sm:col-span-1">
                <label class="block font-pixel text-xs text-ramen-cream/60 mb-1">日本語名</label>
                <input v-model="form.nameJp" placeholder="Japanese name"
                  class="w-full bg-ramen-darker border border-ramen-brown text-ramen-cream px-3 py-2 text-sm focus:border-ramen-orange focus:outline-none" />
              </div>
            </div>
            <div>
              <label class="block font-pixel text-xs text-ramen-cream/60 mb-1">Category *</label>
              <select v-model="form.categoryId" required
                class="w-full bg-ramen-darker border border-ramen-brown text-ramen-cream px-3 py-2 text-sm focus:border-ramen-orange focus:outline-none">
                <option value="" disabled>Select category</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                  {{ getCatStyle(cat.name).emoji }} {{ cat.name }}
                </option>
              </select>
            </div>
            <div>
              <label class="block font-pixel text-xs text-ramen-cream/60 mb-1">Description</label>
              <textarea v-model="form.description" rows="2"
                class="w-full bg-ramen-darker border border-ramen-brown text-ramen-cream px-3 py-2 text-sm focus:border-ramen-orange focus:outline-none" />
            </div>

            <!-- Nutrition grid with Japanese labels -->
            <div>
              <label class="block font-pixel text-xs text-ramen-cream/40 mb-2">Nutrition 栄養</label>
              <div class="grid grid-cols-4 gap-2">
                <div>
                  <label class="block text-xs text-ramen-cream/40 mb-1">Cal</label>
                  <input v-model="form.caloriesPerServing" type="number" step="0.1"
                    class="w-full bg-ramen-darker border border-ramen-brown text-ramen-cream px-2 py-1.5 text-xs focus:border-ramen-orange focus:outline-none" />
                </div>
                <div>
                  <label class="block text-xs text-ramen-cream/40 mb-1">Protein</label>
                  <input v-model="form.proteinG" type="number" step="0.1"
                    class="w-full bg-ramen-darker border border-ramen-brown text-ramen-cream px-2 py-1.5 text-xs focus:border-ramen-orange focus:outline-none" />
                </div>
                <div>
                  <label class="block text-xs text-ramen-cream/40 mb-1">Fat</label>
                  <input v-model="form.fatG" type="number" step="0.1"
                    class="w-full bg-ramen-darker border border-ramen-brown text-ramen-cream px-2 py-1.5 text-xs focus:border-ramen-orange focus:outline-none" />
                </div>
                <div>
                  <label class="block text-xs text-ramen-cream/40 mb-1">Carbs</label>
                  <input v-model="form.carbsG" type="number" step="0.1"
                    class="w-full bg-ramen-darker border border-ramen-brown text-ramen-cream px-2 py-1.5 text-xs focus:border-ramen-orange focus:outline-none" />
                </div>
              </div>
            </div>

            <div class="flex gap-3 pt-3 border-t border-ramen-brown/30">
              <button type="submit"
                class="font-pixel text-xs bg-ramen-red text-ramen-cream px-4 py-2 hover:bg-ramen-orange transition-colors">
                {{ editing ? 'Save' : 'Create' }}
              </button>
              <button type="button" @click="showModal = false"
                class="font-pixel text-xs text-ramen-cream/40 hover:text-ramen-cream transition-colors px-4 py-2">
                Cancel
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Delete Confirmation -->
      <div v-if="showDeleteConfirm" class="fixed inset-0 bg-black/80 flex items-center justify-center z-50 p-4">
        <div class="bg-ramen-dark border-2 border-ramen-red w-full max-w-sm p-6 text-center">
          <div class="text-3xl mb-3">⚠️</div>
          <p class="font-pixel text-sm text-ramen-cream mb-1">Remove ingredient?</p>
          <p class="text-sm text-ramen-cream/40 mb-1">"{{ deletingItem?.name }}"</p>
          <p class="font-pixel text-xs text-ramen-red/60 mb-4">この操作は取り消せません</p>
          <div class="flex justify-center gap-3">
            <button @click="handleDelete"
              class="font-pixel text-xs bg-ramen-red text-ramen-cream px-4 py-2 hover:bg-red-700 transition-colors">
              Delete
            </button>
            <button @click="showDeleteConfirm = false"
              class="font-pixel text-xs text-ramen-cream/40 hover:text-ramen-cream transition-colors px-4 py-2">
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
