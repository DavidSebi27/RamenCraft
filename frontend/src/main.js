import './assets/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'

// Create the Vue app, register plugins, and mount it
const app = createApp(App)
app.use(createPinia())
app.use(router)
app.mount('#app')
