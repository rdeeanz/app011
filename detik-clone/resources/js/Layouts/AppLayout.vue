<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Navigation Header -->
    <header class="bg-white shadow-sm border-b border-gray-200">
      <!-- Top Bar -->
      <div class="bg-detik-red text-white py-1">
        <div class="container mx-auto px-4">
          <div class="flex justify-between items-center text-sm">
            <div class="flex space-x-4">
              <span>{{ new Date().toLocaleDateString('id-ID', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
              }) }}</span>
            </div>
            <div class="flex space-x-4">
              <a href="#" class="hover:underline">RSS</a>
              <a href="#" class="hover:underline">Kontak</a>
              <a href="#" class="hover:underline">Karir</a>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Header -->
      <div class="container mx-auto px-4 py-4">
        <div class="flex items-center justify-between">
          <!-- Logo -->
          <div class="flex-shrink-0">
            <Link href="/" class="text-3xl font-bold text-detik-red">
              DetikClone
            </Link>
          </div>

          <!-- Search Bar -->
          <div class="flex-1 max-w-xl mx-8">
            <form @submit.prevent="handleSearch" class="relative" role="search">
              <label for="search-input" class="sr-only">Cari berita</label>
              <input
                id="search-input"
                ref="searchInputRef"
                v-model="searchQuery"
                type="search"
                placeholder="Cari berita…"
                :disabled="isSearching"
                :aria-busy="isSearching"
                :aria-describedby="searchHintId"
                class="w-full px-4 py-2 pl-10 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-detik-red focus:border-transparent outline-none transition-all disabled:bg-gray-100 disabled:cursor-not-allowed"
                @keydown.esc="handleEscape"
                autocomplete="off"
              >
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg v-if="!isSearching" class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <!-- Loading Spinner -->
                <svg v-else class="animate-spin h-5 w-5 text-detik-red" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" role="status" aria-label="Mencari...">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
              </div>
              <div class="absolute inset-y-0 right-0 flex items-center pr-3 space-x-1">
                <!-- Clear Button -->
                <button
                  v-if="searchQuery && !isSearching"
                  type="button"
                  @click="handleClear"
                  class="text-gray-400 hover:text-gray-600 transition-colors"
                  title="Hapus pencarian (ESC)"
                  aria-label="Hapus pencarian"
                >
                  <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                  </svg>
                </button>
                <!-- Search Button -->
                <button
                  v-if="searchQuery && validatedQuery.length >= 3"
                  type="submit"
                  :disabled="isSearching"
                  class="text-detik-red hover:text-red-700 transition-colors disabled:opacity-50"
                  title="Cari (Enter)"
                  aria-label="Cari berita"
                >
                  <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                  </svg>
                </button>
              </div>
              <!-- Search Hint/Status -->
              <div :id="searchHintId" role="status" aria-live="polite" class="sr-only">
                <span v-if="isSearching">Sedang mencari berita...</span>
                <span v-else-if="searchQuery && validatedQuery.length > 0 && validatedQuery.length < 3">
                  Masukkan minimal 3 karakter, lalu tekan Enter untuk mencari
                </span>
                <span v-else-if="searchQuery && validatedQuery.length >= 3">
                  Tekan Enter untuk mencari
                </span>
              </div>
            </form>
            <!-- Visual Hint -->
            <p 
              v-if="searchQuery && validatedQuery.length > 0 && validatedQuery.length < 3" 
              class="text-xs text-amber-600 mt-1 ml-1"
              aria-hidden="true"
            >
              Minimal 3 karakter, tekan Enter untuk mencari
            </p>
            <p 
              v-else-if="searchQuery && validatedQuery.length >= 3" 
              class="text-xs text-gray-500 mt-1 ml-1"
              aria-hidden="true"
            >
              Tekan Enter untuk mencari
            </p>
          </div>

          <!-- Social Media -->
          <div class="flex space-x-3">
            <a href="#" class="text-gray-600 hover:text-detik-red">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/>
              </svg>
            </a>
            <a href="#" class="text-gray-600 hover:text-detik-red">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M22.46 6c-.77.35-1.6.58-2.46.69.88-.53 1.56-1.37 1.88-2.38-.83.5-1.75.85-2.72 1.05C18.37 4.5 17.26 4 16 4c-2.35 0-4.27 1.92-4.27 4.29 0 .34.04.67.11.98C8.28 9.09 5.11 7.38 3 4.79c-.37.63-.58 1.37-.58 2.15 0 1.49.75 2.81 1.91 3.56-.71 0-1.37-.2-1.95-.5v.03c0 2.08 1.48 3.82 3.44 4.21a4.22 4.22 0 0 1-1.93.07 4.28 4.28 0 0 0 4 2.98 8.521 8.521 0 0 1-5.33 1.84c-.34 0-.68-.02-1.02-.06C3.44 20.29 5.7 21 8.12 21 16 21 20.33 14.46 20.33 8.79c0-.19 0-.37-.01-.56.84-.6 1.56-1.36 2.14-2.23z"/>
              </svg>
            </a>
          </div>
        </div>
      </div>

      <!-- Navigation Menu -->
      <nav class="bg-detik-dark text-white">
        <div class="container mx-auto px-4">
          <div class="flex space-x-8 overflow-x-auto">
            <Link
              href="/"
              class="py-3 px-2 text-sm font-medium hover:text-detik-red border-b-2 border-transparent hover:border-detik-red whitespace-nowrap"
              :class="{ 'border-detik-red text-detik-red': $page.component === 'Home' }"
            >
              Beranda
            </Link>
            <Link
              v-for="category in categories"
              :key="category.id"
              :href="`/kategori/${category.slug}`"
              class="py-3 px-2 text-sm font-medium hover:text-detik-red border-b-2 border-transparent hover:border-detik-red whitespace-nowrap"
            >
              {{ category.name }}
            </Link>
          </div>
        </div>
      </nav>
    </header>

    <!-- Main Content -->
    <main>
      <slot />
    </main>

    <!-- Footer -->
    <footer class="bg-detik-dark text-white mt-16">
      <div class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
          <!-- Company Info -->
          <div class="col-span-1 md:col-span-2">
            <h3 class="text-xl font-bold text-detik-red mb-4">DetikClone</h3>
            <p class="text-gray-300 mb-4">
              Portal berita terdepan Indonesia yang menyajikan informasi terkini, akurat, dan terpercaya.
            </p>
            <div class="flex space-x-4">
              <a href="#" class="text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/>
                </svg>
              </a>
              <a href="#" class="text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M22.56 6c-.77.35-1.6.58-2.46.69.88-.53 1.56-1.37 1.88-2.38-.83.5-1.75.85-2.72 1.05C18.37 4.5 17.26 4 16 4c-2.35 0-4.27 1.92-4.27 4.29 0 .34.04.67.11.98C8.28 9.09 5.11 7.38 3 4.79c-.37.63-.58 1.37-.58 2.15 0 1.49.75 2.81 1.91 3.56-.71 0-1.37-.2-1.95-.5v.03c0 2.08 1.48 3.82 3.44 4.21a4.22 4.22 0 0 1-1.93.07 4.28 4.28 0 0 0 4 2.98 8.521 8.521 0 0 1-5.33 1.84c-.34 0-.68-.02-1.02-.06C3.44 20.29 5.7 21 8.12 21 16 21 20.33 14.46 20.33 8.79c0-.19 0-.37-.01-.56.84-.6 1.56-1.36 2.14-2.23z"/>
                </svg>
              </a>
            </div>
          </div>

          <!-- Quick Links -->
          <div>
            <h4 class="font-semibold mb-4">Navigasi</h4>
            <ul class="space-y-2">
              <li><Link href="/" class="text-gray-300 hover:text-white">Beranda</Link></li>
              <li><a href="#" class="text-gray-300 hover:text-white">Tentang Kami</a></li>
              <li><a href="#" class="text-gray-300 hover:text-white">Kontak</a></li>
              <li><a href="#" class="text-gray-300 hover:text-white">Karir</a></li>
            </ul>
          </div>

          <!-- Legal -->
          <div>
            <h4 class="font-semibold mb-4">Legal</h4>
            <ul class="space-y-2">
              <li><a href="#" class="text-gray-300 hover:text-white">Kebijakan Privasi</a></li>
              <li><a href="#" class="text-gray-300 hover:text-white">Syarat & Ketentuan</a></li>
              <li><a href="#" class="text-gray-300 hover:text-white">Disclaimer</a></li>
            </ul>
          </div>
        </div>

        <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-400">
          <p>&copy; {{ new Date().getFullYear() }} DetikClone. All rights reserved.</p>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'

interface Category {
  id: number
  name: string
  slug: string
}

withDefaults(defineProps<{
  categories?: Category[]
}>(), {
  categories: () => []
})

const page = usePage()
const searchInputRef = ref<HTMLInputElement | null>(null)
const searchQuery = ref('')
const isSearching = ref(false)
const searchHintId = 'search-hint'
const abortController = ref<AbortController | null>(null)
const searchCache = ref<Map<string, any>>(new Map())

// Validasi dan normalisasi query
const validatedQuery = computed(() => {
  return searchQuery.value
    .trim()
    .replace(/\s+/g, ' ') // Normalisasi spasi ganda menjadi single space
    .normalize('NFC') // Unicode normalization
})

// Perform search dengan cache dan AbortController (hanya via Enter key atau tombol submit)
const performSearch = () => {
  const query = validatedQuery.value

  // Check minimum length (3 characters)
  if (query.length < 3) {
    return
  }

  // Check cache
  const cacheKey = query.toLowerCase()
  if (searchCache.value.has(cacheKey)) {
    // Use cached result
    const cachedUrl = searchCache.value.get(cacheKey)
    router.visit(cachedUrl, {
      preserveState: false,
      preserveScroll: false
    })
    return
  }

  // Cancel previous request
  if (abortController.value) {
    abortController.value.abort()
  }

  // Create new AbortController
  abortController.value = new AbortController()

  isSearching.value = true

  // Update URL with query parameter
  const searchUrl = `/cari?q=${encodeURIComponent(query)}`
  
  // Cache the search URL
  searchCache.value.set(cacheKey, searchUrl)

  router.visit(searchUrl, {
    method: 'get',
    preserveState: false,
    preserveScroll: false,
    onFinish: () => {
      isSearching.value = false
      abortController.value = null
    },
    onError: () => {
      isSearching.value = false
      abortController.value = null
    }
  })
}

// Handle form submit (triggered by Enter key or search button)
const handleSearch = () => {
  if (validatedQuery.value.length >= 3) {
    performSearch()
  }
}

// Handle ESC key
const handleEscape = () => {
  searchQuery.value = ''

  // Cancel any ongoing request
  if (abortController.value) {
    abortController.value.abort()
    abortController.value = null
  }

  isSearching.value = false

  // Focus back on input
  searchInputRef.value?.focus()
}

// Handle clear button
const handleClear = () => {
  handleEscape()
}

// Sync URL query param with search field
watch(() => page.url, (newUrl) => {
  if (newUrl.includes('/cari')) {
    const params = new URLSearchParams(newUrl.split('?')[1] || '')
    const query = params.get('q')
    if (query && query !== searchQuery.value) {
      searchQuery.value = query
    }
  } else if (!newUrl.includes('/cari') && searchQuery.value) {
    // Clear search query when navigating away from search page
    searchQuery.value = ''
  }
}, { immediate: true })

// Handle browser back/forward
const handlePopstate = () => {
  const params = new URLSearchParams(window.location.search)
  const query = params.get('q')
  if (query) {
    searchQuery.value = query
  } else {
    searchQuery.value = ''
  }
}

// Keyboard shortcut to focus search (/)
const handleKeydown = (e: KeyboardEvent) => {
  // Focus search field when "/" is pressed (and not in an input/textarea)
  if (e.key === '/' && !['INPUT', 'TEXTAREA'].includes((e.target as HTMLElement).tagName)) {
    e.preventDefault()
    searchInputRef.value?.focus()
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleKeydown)
  window.addEventListener('popstate', handlePopstate)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown)
  window.removeEventListener('popstate', handlePopstate)
  
  // Cleanup
  if (abortController.value) {
    abortController.value.abort()
  }
})
</script>