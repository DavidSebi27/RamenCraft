<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import NavBar from '@/components/organisms/NavBar/NavBar.vue'

const router = useRouter()
const auth = useAuthStore()

const username = ref('')
const email = ref('')
const password = ref('')
const confirmPassword = ref('')
const localError = ref('')

function handleRegister() {
  localError.value = ''
  auth.error = null

  if (!username.value || !email.value || !password.value) {
    localError.value = 'All fields are required'
    return
  }

  if (password.value !== confirmPassword.value) {
    localError.value = 'Passwords do not match'
    return
  }

  if (password.value.length < 6) {
    localError.value = 'Password must be at least 6 characters'
    return
  }

  auth.register(username.value, email.value, password.value)
    .then(() => {
      router.push('/play')
    })
    .catch(() => {
      // Error is already set in the store
    })
}
</script>

<template>
  <div class="min-h-screen bg-ramen-darker">
    <NavBar />
    <div class="flex items-center justify-center p-4 mt-8">
      <div class="w-full max-w-sm">
        <h1 class="font-pixel text-xl text-ramen-orange mb-6 text-center">Register</h1>

        <div v-if="localError || auth.error" class="bg-ramen-red/20 border border-ramen-red text-ramen-cream text-sm px-3 py-2 mb-4">
          {{ localError || auth.error }}
        </div>

        <form @submit.prevent="handleRegister" class="space-y-4">
          <div>
            <label class="block font-pixel text-xs text-ramen-cream mb-2">Username</label>
            <input
              v-model="username"
              type="text"
              class="w-full bg-ramen-dark border border-ramen-brown text-ramen-cream px-3 py-2 text-sm focus:border-ramen-orange focus:outline-none"
              placeholder="Choose a username"
            />
          </div>

          <div>
            <label class="block font-pixel text-xs text-ramen-cream mb-2">Email</label>
            <input
              v-model="email"
              type="email"
              class="w-full bg-ramen-dark border border-ramen-brown text-ramen-cream px-3 py-2 text-sm focus:border-ramen-orange focus:outline-none"
              placeholder="Enter email"
            />
          </div>

          <div>
            <label class="block font-pixel text-xs text-ramen-cream mb-2">Password</label>
            <input
              v-model="password"
              type="password"
              class="w-full bg-ramen-dark border border-ramen-brown text-ramen-cream px-3 py-2 text-sm focus:border-ramen-orange focus:outline-none"
              placeholder="Create password (min 6 chars)"
            />
          </div>

          <div>
            <label class="block font-pixel text-xs text-ramen-cream mb-2">Confirm Password</label>
            <input
              v-model="confirmPassword"
              type="password"
              class="w-full bg-ramen-dark border border-ramen-brown text-ramen-cream px-3 py-2 text-sm focus:border-ramen-orange focus:outline-none"
              placeholder="Confirm password"
            />
          </div>

          <button
            type="submit"
            :disabled="auth.loading"
            class="w-full font-pixel text-xs bg-ramen-red text-ramen-cream px-4 py-3 hover:bg-ramen-orange transition-colors disabled:opacity-50"
          >
            {{ auth.loading ? 'CREATING...' : 'CREATE ACCOUNT' }}
          </button>
        </form>

        <p class="text-center text-sm text-ramen-cream mt-4">
          Already have an account?
          <router-link to="/login" class="text-ramen-neon hover:underline">Login</router-link>
        </p>
      </div>
    </div>
  </div>
</template>
