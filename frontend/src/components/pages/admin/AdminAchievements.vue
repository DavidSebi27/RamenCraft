<script setup>
import { ref, onMounted } from 'vue'
import NavBar from '@/components/organisms/NavBar/NavBar.vue'
import {
  fetchAchievements,
  createAchievement,
  updateAchievement,
  deleteAchievement,
} from '@/services/api'

const achievements = ref([])
const total = ref(0)
const page = ref(1)
const limit = 20
const loading = ref(false)
const error = ref('')

const showModal = ref(false)
const editing = ref(null)
const form = ref(getEmptyForm())

function getEmptyForm() {
  return { name: '', description: '', icon: '', requirementType: '', requirementValue: '' }
}

const showDeleteConfirm = ref(false)
const deletingItem = ref(null)

async function loadData() {
  loading.value = true; error.value = ''
  try {
    const result = await fetchAchievements({ page: page.value, limit })
    achievements.value = result.data; total.value = result.total
  } catch (e) { error.value = e.response?.data?.error || 'Failed to load' }
  finally { loading.value = false }
}

onMounted(loadData)

function openCreate() { editing.value = null; form.value = getEmptyForm(); showModal.value = true }

function openEdit(item) {
  editing.value = item
  form.value = {
    name: item.name, description: item.description || '', icon: item.icon || '',
    requirementType: item.requirementType || '', requirementValue: item.requirementValue ?? '',
  }
  showModal.value = true
}

async function handleSave() {
  error.value = ''
  try {
    const body = { ...form.value, requirementValue: form.value.requirementValue === '' ? null : Number(form.value.requirementValue) }
    if (editing.value) { await updateAchievement(editing.value.id, body) }
    else { await createAchievement(body) }
    showModal.value = false; await loadData()
  } catch (e) { error.value = e.response?.data?.error || 'Save failed' }
}

function confirmDelete(item) { deletingItem.value = item; showDeleteConfirm.value = true }

async function handleDelete() {
  error.value = ''
  try {
    await deleteAchievement(deletingItem.value.id)
    showDeleteConfirm.value = false; deletingItem.value = null; await loadData()
  } catch (e) { error.value = e.response?.data?.error || 'Delete failed' }
}

function prevPage() { if (page.value > 1) { page.value--; loadData() } }
function nextPage() { if (page.value * limit < total.value) { page.value++; loadData() } }

// Requirement type styling
function reqTypeLabel(type) {
  const map = {
    bowls_served: '🍜 Bowls Served',
    unique_ingredients: '🧪 Unique Ingredients',
    total_xp: '⭐ Total XP',
    perfect_bowls: '✨ Perfect Bowls',
  }
  return map[type] || type || '—'
}
</script>

<template>
  <div class="min-h-screen bg-ramen-darker">
    <NavBar />
    <div class="max-w-5xl mx-auto p-6">

      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center gap-3 mb-1">
          <span class="text-2xl">🏆</span>
          <h1 class="font-pixel text-xl text-ramen-orange">Achievements</h1>
          <span class="font-pixel text-xs text-ramen-cream/30">実績管理</span>
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
            + New Achievement
          </button>
        </div>
      </div>

      <div v-if="error" class="bg-ramen-red/20 border-l-4 border-ramen-red text-ramen-cream text-sm px-4 py-3 mb-6">
        {{ error }}
      </div>

      <div v-if="loading" class="text-center py-12">
        <div class="font-pixel text-sm text-ramen-orange animate-pulse">Loading achievements...</div>
        <div class="text-ramen-cream/30 text-xs mt-2">お待ちください</div>
      </div>

      <!-- Achievement cards -->
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div v-for="item in achievements" :key="item.id"
          class="bg-ramen-dark border border-ramen-brown/50 p-5 group hover:border-ramen-gold/40 transition-colors relative overflow-hidden">

          <!-- Decorative corner -->
          <div class="absolute top-0 right-0 w-8 h-8 bg-ramen-gold/5"></div>

          <div class="flex items-start gap-4">
            <!-- Badge icon -->
            <div class="w-12 h-12 bg-ramen-darker border border-ramen-gold/30 flex items-center justify-center shrink-0">
              <span class="text-xl">{{ item.icon || '🎖️' }}</span>
            </div>

            <div class="flex-1 min-w-0">
              <h3 class="font-pixel text-xs text-ramen-gold mb-1">{{ item.name }}</h3>
              <p v-if="item.description" class="text-xs text-ramen-cream/50 mb-2 line-clamp-2">{{ item.description }}</p>

              <!-- Requirement -->
              <div class="flex items-center gap-2">
                <span class="text-xs text-ramen-cream/30">{{ reqTypeLabel(item.requirementType) }}</span>
                <span v-if="item.requirementValue !== null" class="font-pixel text-xs text-ramen-orange">
                  × {{ item.requirementValue }}
                </span>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex gap-2 mt-3 pt-2 border-t border-ramen-brown/20 opacity-0 group-hover:opacity-100 transition-opacity">
            <button @click="openEdit(item)" class="font-pixel text-xs text-ramen-neon hover:text-ramen-gold">Edit</button>
            <span class="text-ramen-brown/30">|</span>
            <button @click="confirmDelete(item)" class="font-pixel text-xs text-ramen-red/60 hover:text-ramen-red">Delete</button>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div class="flex items-center justify-between mt-8 pt-4 border-t border-ramen-brown/30">
        <span class="text-ramen-cream/40 text-xs">{{ total }} achievements unlockable</span>
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
            <span class="text-lg">{{ editing ? '✏️' : '🏆' }}</span>
            <h2 class="font-pixel text-sm text-ramen-orange">
              {{ editing ? 'Edit Achievement' : 'New Achievement' }}
            </h2>
          </div>
          <form @submit.prevent="handleSave" class="space-y-3">
            <div>
              <label class="block font-pixel text-xs text-ramen-cream/60 mb-1">Name *</label>
              <input v-model="form.name" required
                class="w-full bg-ramen-darker border border-ramen-brown text-ramen-cream px-3 py-2 text-sm focus:border-ramen-orange focus:outline-none" />
            </div>
            <div>
              <label class="block font-pixel text-xs text-ramen-cream/60 mb-1">Description</label>
              <textarea v-model="form.description" rows="2"
                class="w-full bg-ramen-darker border border-ramen-brown text-ramen-cream px-3 py-2 text-sm focus:border-ramen-orange focus:outline-none" />
            </div>
            <div>
              <label class="block font-pixel text-xs text-ramen-cream/60 mb-1">Icon</label>
              <input v-model="form.icon" placeholder="Emoji or sprite path"
                class="w-full bg-ramen-darker border border-ramen-brown text-ramen-cream px-3 py-2 text-sm focus:border-ramen-orange focus:outline-none" />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block font-pixel text-xs text-ramen-cream/60 mb-1">Requirement Type</label>
                <select v-model="form.requirementType"
                  class="w-full bg-ramen-darker border border-ramen-brown text-ramen-cream px-3 py-2 text-sm focus:border-ramen-orange focus:outline-none">
                  <option value="">None</option>
                  <option value="bowls_served">Bowls Served</option>
                  <option value="unique_ingredients">Unique Ingredients</option>
                  <option value="total_xp">Total XP</option>
                  <option value="perfect_bowls">Perfect Bowls</option>
                </select>
              </div>
              <div>
                <label class="block font-pixel text-xs text-ramen-cream/60 mb-1">Value</label>
                <input v-model="form.requirementValue" type="number" placeholder="e.g. 10"
                  class="w-full bg-ramen-darker border border-ramen-brown text-ramen-cream px-3 py-2 text-sm focus:border-ramen-orange focus:outline-none" />
              </div>
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
          <p class="font-pixel text-sm text-ramen-cream mb-1">Remove achievement?</p>
          <p class="text-sm text-ramen-cream/40 mb-1">"{{ deletingItem?.name }}"</p>
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
