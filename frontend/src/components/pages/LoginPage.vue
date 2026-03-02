<script setup>
// LoginPage — Login form
// In Phase 1 this is just a static form. JWT auth comes in Phase 4.
</script>

<template>
  <div class="min-h-screen bg-ramen-darker flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
      <h1 class="font-pixel text-xl text-ramen-orange mb-6 text-center">Login</h1>

      <form @submit.prevent class="space-y-4">
        <div>
          <label class="block font-pixel text-xs text-ramen-cream mb-2">Username</label>
          <input
            type="text"
            class="w-full bg-ramen-dark border border-ramen-brown text-ramen-cream px-3 py-2 text-sm focus:border-ramen-orange focus:outline-none"
            placeholder="Enter username"
          />
        </div>

        <div>
          <label class="block font-pixel text-xs text-ramen-cream mb-2">Password</label>
          <input
            type="password"
            class="w-full bg-ramen-dark border border-ramen-brown text-ramen-cream px-3 py-2 text-sm focus:border-ramen-orange focus:outline-none"
            placeholder="Enter password"
          />
        </div>

        <button
          type="submit"
          class="w-full font-pixel text-xs bg-ramen-red text-ramen-cream px-4 py-3 hover:bg-ramen-orange transition-colors"
        >
          LOG IN
        </button>
      </form>

      <p class="text-center text-sm text-ramen-cream mt-4">
        No account?
        <router-link to="/register" class="text-ramen-neon hover:underline">Register</router-link>
      </p>

      <router-link to="/" class="text-ramen-neon text-sm mt-4 inline-block hover:underline">
        &larr; Back to Home
      </router-link>
    </div>
  </div>
</template>
