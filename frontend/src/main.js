import './assets/main.css'

import { createApp } from 'vue'
import App from './App.vue'
import router from './router'

// Create the Vue app, register the router plugin, and mount it
// The router plugin enables <router-view> and <router-link> globally
const app = createApp(App)
app.use(router)
app.mount('#app')
