import { createApp } from 'vue'
import { createRouter, createWebHistory } from 'vue-router'
import { createPinia } from 'pinia'

// Main Components
import Homepage from './components/homepage/Homepage.vue'
import ArticlePage from './components/article/ArticlePage.vue'

// Global Components
import LazyImage from './components/common/LazyImage.vue'
import LoadingSpinner from './components/common/LoadingSpinner.vue'

// Router Configuration
const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      name: 'home',
      component: Homepage,
      meta: { title: 'Beranda - Detik Clone' }
    },
    {
      path: '/artikel/:slug',
      name: 'article',
      component: ArticlePage,
      meta: { title: 'Artikel - Detik Clone' }
    }
  ],
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) return savedPosition
    if (to.hash) return { el: to.hash, behavior: 'smooth' }
    return { top: 0, behavior: 'smooth' }
  }
})

// Create Vue App
const app = createApp({
  template: '<router-view />'
})

// Plugins
app.use(router)
app.use(createPinia())

// Global Components
app.component('LazyImage', LazyImage)
app.component('LoadingSpinner', LoadingSpinner)

// Mount App
app.mount('#app')
