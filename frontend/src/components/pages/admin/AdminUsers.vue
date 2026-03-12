<script setup>
import { ref, onMounted } from 'vue'
import NavBar from '@/components/organisms/NavBar/NavBar.vue'
import { fetchUsers, updateUser, deleteUser, getStoredUser } from '@/services/api'

const users = ref([])
const total = ref(0)
const page = ref(1)
const limit = 20
const loading = ref(false)
const error = ref('')
const currentUser = getStoredUser()

const showModal = ref(false)
const editing = ref(null)
const form = ref({ username: '', email: '', role: '' })

const showDeleteConfirm = ref(false)
const deletingItem = ref(null)

async function loadData() {
  loading.value = true; error.value = ''
  try {
    const result = await fetchUsers({ page: page.value, limit })
    users.value = result.data; total.value = result.total
  } catch (e) { error.value = e.response?.data?.error || 'Failed to load' }
  finally { loading.value = false }
}

onMounted(loadData)

function openEdit(item) {
  editing.value = item
  form.value = { username: item.username, email: item.email, role: item.role }
  showModal.value = true
}

async function handleSave() {
  error.value = ''
  try {
    await updateUser(editing.value.id, form.value)
    showModal.value = false; await loadData()
  } catch (e) { error.value = e.response?.data?.error || 'Save failed' }
}

function confirmDelete(item) { deletingItem.value = item; showDeleteConfirm.value = true }

async function handleDelete() {
  error.value = ''
  try {
    await deleteUser(deletingItem.value.id)
    showDeleteConfirm.value = false; deletingItem.value = null; await loadData()
  } catch (e) { error.value = e.response?.data?.error || 'Delete failed' }
}

function prevPage() { if (page.value > 1) { page.value--; loadData() } }
function nextPage() { if (page.value * limit < total.value) { page.value++; loadData() } }

const ranks = {
  minarai: { label: '見習い', title: 'Apprentice', color: 'text-ramen-cream/60', bar: 'w-[20%] bg-ramen-cream/30' },
  jouren: { label: '常連', title: 'Regular', color: 'text-green-400', bar: 'w-[40%] bg-green-500/50' },
  tsuu: { label: '通', title: 'Connoisseur', color: 'text-blue-400', bar: 'w-[60%] bg-blue-500/50' },
  shokunin: { label: '職人', title: 'Artisan', color: 'text-purple-400', bar: 'w-[80%] bg-purple-500/50' },
  taisho: { label: '大将', title: 'Master', color: 'text-ramen-gold', bar: 'w-full bg-ramen-gold/50' },
}

function getRank(rank) {
  return ranks[rank] || ranks.minarai
}
</script>

<template>
  <div class="min-h-screen bg-ramen-darker">
    <NavBar />
    <div class="max-w-5xl mx-auto p-6">

      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center gap-3 mb-1">
          <span class="text-2xl">👥</span>
          <h1 class="font-pixel text-xl text-ramen-orange">Users</h1>
          <span class="font-pixel text-xs text-ramen-cream/30">ユーザー管理</span>
        </div>
        <div class="flex items-center gap-2 mb-4">
          <div class="h-px flex-1 bg-gradient-to-r from-ramen-orange/60 via-ramen-brown to-transparent"></div>
          <span class="text-ramen-cream/20 text-xs">◆</span>
          <div class="h-px flex-1 bg-gradient-to-l from-ramen-orange/60 via-ramen-brown to-transparent"></div>
        </div>
        <router-link to="/admin" class="text-ramen-neon text-sm hover:underline">&larr; Back to Kitchen</router-link>
      </div>

      <div v-if="error" class="bg-ramen-red/20 border-l-4 border-ramen-red text-ramen-cream text-sm px-4 py-3 mb-6">
        {{ error }}
      </div>

      <div v-if="loading" class="text-center py-12">
        <div class="font-pixel text-sm text-ramen-orange animate-pulse">Loading users...</div>
        <div class="text-ramen-cream/30 text-xs mt-2">お待ちください</div>
      </div>

      <!-- User cards -->
      <div v-else class="space-y-3">
        <div v-for="item in users" :key="item.id"
          class="bg-ramen-dark border border-ramen-brown/50 p-4 group hover:border-ramen-brown transition-colors"
          :class="item.id === currentUser?.id ? 'border-l-4 border-l-ramen-neon' : ''">

          <div class="flex items-center gap-4">
            <!-- Avatar placeholder -->
            <div class="w-11 h-11 bg-ramen-darker border border-ramen-brown flex items-center justify-center shrink-0">
              <span class="font-pixel text-sm" :class="item.role === 'admin' ? 'text-ramen-neon' : 'text-ramen-orange'">
                {{ item.username.charAt(0).toUpperCase() }}
              </span>
            </div>

            <!-- Info -->
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-0.5">
                <span class="font-pixel text-xs text-ramen-cream">{{ item.username }}</span>
                <span v-if="item.role === 'admin'"
                  class="font-pixel text-xs text-ramen-neon bg-ramen-neon/10 px-1.5 py-0.5 border border-ramen-neon/30">
                  ADMIN
                </span>
                <span v-if="item.id === currentUser?.id"
                  class="text-xs text-ramen-cream/30">(you)</span>
              </div>
              <div class="text-xs text-ramen-cream/40">{{ item.email }}</div>
            </div>

            <!-- Rank + XP -->
            <div class="hidden sm:block text-right min-w-[140px]">
              <div class="flex items-center justify-end gap-2 mb-1">
                <span :class="getRank(item.currentRank).color" class="font-pixel text-xs">
                  {{ getRank(item.currentRank).label }}
                </span>
                <span class="text-xs text-ramen-cream/30">{{ getRank(item.currentRank).title }}</span>
              </div>
              <!-- XP bar -->
              <div class="h-1.5 bg-ramen-darker rounded-full overflow-hidden">
                <div :class="getRank(item.currentRank).bar" class="h-full transition-all"></div>
              </div>
              <div class="text-xs text-ramen-gold mt-0.5">{{ item.totalXp }} XP</div>
            </div>

            <!-- Actions -->
            <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
              <button @click="openEdit(item)" class="font-pixel text-xs text-ramen-neon hover:text-ramen-gold">Edit</button>
              <button v-if="item.id !== currentUser?.id" @click="confirmDelete(item)"
                class="font-pixel text-xs text-ramen-red/60 hover:text-ramen-red">Del</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div class="flex items-center justify-between mt-8 pt-4 border-t border-ramen-brown/30">
        <span class="text-ramen-cream/40 text-xs">{{ total }} users registered</span>
        <div class="flex items-center gap-3">
          <button @click="prevPage" :disabled="page <= 1"
            class="font-pixel text-xs text-ramen-cream disabled:text-ramen-cream/20 hover:text-ramen-orange">◄ Prev</button>
          <span class="font-pixel text-xs text-ramen-gold">{{ page }}</span>
          <button @click="nextPage" :disabled="page * limit >= total"
            class="font-pixel text-xs text-ramen-cream disabled:text-ramen-cream/20 hover:text-ramen-orange">Next ►</button>
        </div>
      </div>

      <!-- Edit Modal -->
      <div v-if="showModal" class="fixed inset-0 bg-black/80 flex items-center justify-center z-50 p-4">
        <div class="bg-ramen-dark border-2 border-ramen-brown w-full max-w-md p-6 pixel-border">
          <div class="flex items-center gap-2 mb-4">
            <span class="text-lg">✏️</span>
            <h2 class="font-pixel text-sm text-ramen-orange">Edit User</h2>
            <span class="font-pixel text-xs text-ramen-cream/20">ユーザー編集</span>
          </div>
          <form @submit.prevent="handleSave" class="space-y-3">
            <div>
              <label class="block font-pixel text-xs text-ramen-cream/60 mb-1">Username</label>
              <input v-model="form.username" required
                class="w-full bg-ramen-darker border border-ramen-brown text-ramen-cream px-3 py-2 text-sm focus:border-ramen-orange focus:outline-none" />
            </div>
            <div>
              <label class="block font-pixel text-xs text-ramen-cream/60 mb-1">Email</label>
              <input v-model="form.email" type="email" required
                class="w-full bg-ramen-darker border border-ramen-brown text-ramen-cream px-3 py-2 text-sm focus:border-ramen-orange focus:outline-none" />
            </div>
            <div>
              <label class="block font-pixel text-xs text-ramen-cream/60 mb-1">Role</label>
              <div class="flex gap-3">
                <label class="flex items-center gap-2 cursor-pointer px-3 py-2 border transition-colors"
                  :class="form.role === 'player' ? 'border-ramen-orange bg-ramen-orange/10' : 'border-ramen-brown/30 hover:border-ramen-brown'">
                  <input type="radio" v-model="form.role" value="player" class="hidden" />
                  <span class="font-pixel text-xs text-ramen-cream">🍜 Player</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer px-3 py-2 border transition-colors"
                  :class="form.role === 'admin' ? 'border-ramen-neon bg-ramen-neon/10' : 'border-ramen-brown/30 hover:border-ramen-brown'">
                  <input type="radio" v-model="form.role" value="admin" class="hidden" />
                  <span class="font-pixel text-xs text-ramen-cream">⚡ Admin</span>
                </label>
              </div>
            </div>
            <div class="flex gap-3 pt-3 border-t border-ramen-brown/30">
              <button type="submit"
                class="font-pixel text-xs bg-ramen-red text-ramen-cream px-4 py-2 hover:bg-ramen-orange transition-colors">
                Save
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
          <p class="font-pixel text-sm text-ramen-cream mb-1">Remove user?</p>
          <p class="text-sm text-ramen-cream/40 mb-1">"{{ deletingItem?.username }}" and all their data</p>
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
