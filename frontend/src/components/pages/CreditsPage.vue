<script setup>
/**
 * CreditsPage — Credits and attributions for RamenCraft
 */
import NavBar from '@/components/organisms/NavBar/NavBar.vue'
</script>

<template>
  <div class="min-h-screen bg-ramen-darker">
    <NavBar />
    <div class="max-w-2xl mx-auto px-4 py-12">

      <h1 class="font-pixel text-xl text-ramen-gold text-center mb-2">Credits</h1>
      <div class="text-center text-ramen-cream/30 text-xs mb-10">Thanks for playing RamenCraft!</div>

      <!-- Game -->
      <div class="space-y-8">
        <div class="border border-ramen-gold/30 bg-ramen-dark p-5">
          <div class="font-pixel text-[10px] text-ramen-gold mb-2">Game Design & Development</div>
          <div class="text-sm text-ramen-cream font-bold">David Sebestyen</div>
          <div class="text-xs text-ramen-cream/50 mt-1">Web Development 2 — Inholland University</div>
        </div>

        <!-- Art -->
        <div class="border border-ramen-orange/30 bg-ramen-dark p-5">
          <div class="font-pixel text-[10px] text-ramen-orange mb-2">Pixel Art & Sprites</div>
          <div class="text-sm text-ramen-cream font-bold">Panna Ehleiter</div>
          <div class="text-xs text-ramen-cream/50 mt-1">
            All ingredient icons, bowl sprites, in-bowl layers, restaurant backgrounds, and loading animation
          </div>
        </div>

        <!-- Code Assistance -->
        <div class="border border-ramen-neon/30 bg-ramen-dark p-5">
          <div class="font-pixel text-[10px] text-ramen-neon mb-2">Development Assistance</div>
          <div class="text-sm text-ramen-cream font-bold">Claude (Anthropic)</div>
          <div class="text-xs text-ramen-cream/50 mt-1">
            AI pair programming and development assistance
          </div>
        </div>

        <!-- External Assets -->
        <div class="border border-ramen-cream/20 bg-ramen-dark p-5">
          <div class="font-pixel text-[10px] text-ramen-cream/60 mb-3">External Assets</div>

          <div class="space-y-3">
            <div>
              <div class="text-sm text-ramen-cream">Sound Effects</div>
              <div class="text-xs text-ramen-cream/40">
                Kenney.nl — UI Audio, Interface Sounds, Digital Audio
              </div>
              <div class="text-xs text-ramen-neon/40">License: CC0 (Public Domain)</div>
            </div>

            <div>
              <div class="text-sm text-ramen-cream">Background Music</div>
              <div class="text-xs text-ramen-cream/40">
                "The Quiet Spy" — HydroGene (High Quality 8-bit Musics)
              </div>
              <div class="text-xs text-ramen-neon/40">License: CC0 (Public Domain)</div>
            </div>

            <div>
              <div class="text-sm text-ramen-cream">Loading Animation</div>
              <div class="text-xs text-ramen-cream/40">
                Pixel Art Loading Animation — FalcoDJ (itch.io)
              </div>
            </div>

            <div>
              <div class="text-sm text-ramen-cream">Nutrition Data</div>
              <div class="text-xs text-ramen-cream/40">
                Open Food Facts — open database of food products
              </div>
              <div class="text-xs text-ramen-neon/40">License: Open Database License (ODbL)</div>
            </div>

            <div>
              <div class="text-sm text-ramen-cream">Font</div>
              <div class="text-xs text-ramen-cream/40">
                "Press Start 2P" — CodeMan38 (Google Fonts)
              </div>
              <div class="text-xs text-ramen-neon/40">License: OFL (SIL Open Font License)</div>
            </div>
          </div>
        </div>

        <!-- Tech Stack -->
        <div class="border border-ramen-brown bg-ramen-dark p-5">
          <div class="font-pixel text-[10px] text-ramen-cream/40 mb-3">Built With</div>
          <div class="flex flex-wrap gap-2">
            <span class="text-xs text-ramen-cream/60 bg-ramen-darker px-2 py-1">Vue 3</span>
            <span class="text-xs text-ramen-cream/60 bg-ramen-darker px-2 py-1">Pinia</span>
            <span class="text-xs text-ramen-cream/60 bg-ramen-darker px-2 py-1">Tailwind CSS</span>
            <span class="text-xs text-ramen-cream/60 bg-ramen-darker px-2 py-1">Vite</span>
            <span class="text-xs text-ramen-cream/60 bg-ramen-darker px-2 py-1">PHP</span>
            <span class="text-xs text-ramen-cream/60 bg-ramen-darker px-2 py-1">FastRoute</span>
            <span class="text-xs text-ramen-cream/60 bg-ramen-darker px-2 py-1">MySQL</span>
            <span class="text-xs text-ramen-cream/60 bg-ramen-darker px-2 py-1">Docker</span>
            <span class="text-xs text-ramen-cream/60 bg-ramen-darker px-2 py-1">JWT</span>
            <span class="text-xs text-ramen-cream/60 bg-ramen-darker px-2 py-1">Axios</span>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="text-center mt-10">
        <div class="font-pixel text-[8px] text-ramen-cream/20">RamenCraft 2026</div>
      </div>
    </div>
  </div>
</template>
