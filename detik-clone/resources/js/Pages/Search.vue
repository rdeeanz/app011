<template>
  <Head>
    <title>{{ query ? `Hasil Pencarian: ${query}` : 'Pencarian' }} - DetikClone</title>
    <meta name="description" :content="`Hasil pencarian untuk '${query}' di DetikClone. Temukan berita terkini yang relevan dengan kata kunci Anda.`">
  </Head>

  <AppLayout :categories="categories">
    <div class="container mx-auto px-4 py-8">
      <!-- Search Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-4">
          {{ query ? 'Hasil Pencarian' : 'Pencarian Berita' }}
        </h1>
        
        <!-- Search Box -->
        <form @submit.prevent="performSearch" class="max-w-2xl">
          <div class="relative">
            <input
              v-model="searchQuery"
              type="search"
              placeholder="Cari berita..."
              class="w-full px-4 py-3 pl-12 pr-4 text-lg border border-gray-300 rounded-lg focus:ring-2 focus:ring-detik-red focus:border-transparent"
              autofocus
            >
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
              <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
              </svg>
            </div>
            <button
              v-if="searchQuery"
              type="button"
              @click="clearSearch"
              class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600"
            >
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
        </form>

        <!-- Search Info -->
        <div v-if="query" class="mt-4 text-gray-600">
          <p>
            Menampilkan hasil untuk: <strong class="text-gray-900">"{{ query }}"</strong>
            <span v-if="articles.total > 0" class="ml-2">
              ({{ articles.total }} {{ articles.total === 1 ? 'hasil' : 'hasil' }} ditemukan)
            </span>
          </p>
        </div>
      </div>

      <!-- Results Section -->
      <div v-if="query">
        <!-- No Results -->
        <div v-if="articles.data && articles.data.length === 0" class="text-center py-16">
          <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
          </svg>
          <h3 class="text-xl font-semibold text-gray-900 mb-2">Tidak Ada Hasil Ditemukan</h3>
          <p class="text-gray-600 mb-6">Maaf, kami tidak menemukan berita yang cocok dengan pencarian Anda.</p>
          <div class="max-w-md mx-auto text-left">
            <p class="text-sm text-gray-600 mb-2">Saran:</p>
            <ul class="text-sm text-gray-600 list-disc list-inside space-y-1">
              <li>Periksa ejaan kata kunci Anda</li>
              <li>Coba gunakan kata kunci yang lebih umum</li>
              <li>Coba gunakan kata kunci yang berbeda</li>
              <li>Gunakan lebih sedikit kata kunci</li>
            </ul>
          </div>
        </div>

        <!-- Results Grid -->
        <div v-else-if="articles.data && articles.data.length > 0">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <article 
              v-for="article in articles.data" 
              :key="article.id"
              class="group bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-lg transition-all duration-300"
            >
              <Link :href="`/articles/${article.slug}`">
                <!-- Image -->
                <div class="relative h-48 overflow-hidden">
                  <img 
                    :src="article.featured_image || '/images/placeholder.jpg'"
                    :alt="article.title"
                    class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
                    loading="lazy"
                  >
                  <!-- Category Badge -->
                  <div class="absolute top-3 left-3">
                    <span 
                      class="text-white px-3 py-1 rounded-full text-xs font-semibold"
                      :style="{ backgroundColor: article.category?.color || '#dc2626' }"
                    >
                      {{ article.category?.name || 'News' }}
                    </span>
                  </div>
                </div>

                <!-- Content -->
                <div class="p-4">
                  <h3 class="font-bold text-gray-900 group-hover:text-detik-red line-clamp-2 mb-2 text-lg">
                    {{ article.title }}
                  </h3>
                  <p class="text-gray-600 text-sm line-clamp-3 mb-3">
                    {{ article.excerpt }}
                  </p>
                  
                  <!-- Meta -->
                  <div class="flex items-center justify-between text-xs text-gray-500">
                    <div class="flex items-center space-x-2">
                      <span>{{ article.author?.name || 'Redaksi' }}</span>
                    </div>
                    <time :datetime="article.published_at">
                      {{ formatDate(article.published_at) }}
                    </time>
                  </div>
                </div>
              </Link>
            </article>
          </div>

          <!-- Pagination -->
          <div v-if="hasPagination && (articles as PaginatedArticles).last_page > 1" class="mt-8">
            <nav class="flex items-center justify-center space-x-2" aria-label="Pagination">
              <!-- Previous Button -->
              <Link
                v-if="(articles as PaginatedArticles).prev_page_url"
                :href="(articles as PaginatedArticles).prev_page_url!"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
              </Link>
              <span v-else class="px-4 py-2 text-sm font-medium text-gray-400 cursor-not-allowed">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
              </span>

              <!-- Page Numbers -->
              <template v-for="page in visiblePages" :key="page">
                <Link
                  v-if="page !== '...'"
                  :href="getPageUrl(page as number)"
                  :class="[
                    'px-4 py-2 text-sm font-medium rounded-md transition-colors',
                    page === (articles as PaginatedArticles).current_page
                      ? 'bg-detik-red text-white'
                      : 'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50'
                  ]"
                >
                  {{ page }}
                </Link>
                <span v-else class="px-4 py-2 text-sm text-gray-500">...</span>
              </template>

              <!-- Next Button -->
              <Link
                v-if="(articles as PaginatedArticles).next_page_url"
                :href="(articles as PaginatedArticles).next_page_url!"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
              </Link>
              <span v-else class="px-4 py-2 text-sm font-medium text-gray-400 cursor-not-allowed">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
              </span>
            </nav>

            <!-- Pagination Info -->
            <div class="text-center mt-4 text-sm text-gray-600">
              Menampilkan {{ startItem }} - {{ endItem }} dari {{ (articles as PaginatedArticles).total }} hasil
            </div>
          </div>
        </div>
      </div>

      <!-- Initial State (No Search Yet) -->
      <div v-else class="text-center py-16">
        <svg class="mx-auto h-20 w-20 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
        </svg>
        <h3 class="text-xl font-semibold text-gray-900 mb-2">Cari Berita</h3>
        <p class="text-gray-600">Masukkan kata kunci untuk mencari berita yang Anda inginkan</p>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

interface Category {
  id: number
  name: string
  slug: string
  color?: string
}

interface Article {
  id: number
  title: string
  slug: string
  excerpt: string
  featured_image?: string
  published_at: string
  views_count: number
  category?: {
    id: number
    name: string
    slug: string
    color?: string
  }
  author?: {
    id: number
    name: string
    avatar?: string
  }
}

interface PaginatedArticles {
  data: Article[]
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number
  to: number
  prev_page_url: string | null
  next_page_url: string | null
}

const props = defineProps<{
  query?: string
  articles: PaginatedArticles | { data: never[], total: 0 }
  categories?: Category[]
}>()

const searchQuery = ref(props.query || '')

// Type guard to check if articles has pagination fields
const hasPagination = computed(() => {
  return props.articles 
    && typeof (props.articles as any).last_page === 'number' 
    && typeof (props.articles as any).current_page === 'number'
})

// Helper function to generate page URLs consistently
const getPageUrl = (page: number): string => {
  // Try to use backend-provided URL as a base
  const baseUrl = (props.articles as any).prev_page_url || (props.articles as any).next_page_url
  
  if (baseUrl) {
    try {
      const url = new URL(baseUrl, window.location.origin)
      url.searchParams.set('page', String(page))
      return url.pathname + url.search
    } catch (e) {
      // Fallback to manual construction
    }
  }
  
  // Fallback: construct URL manually
  const params = new URLSearchParams()
  if (props.query) {
    params.set('q', props.query)
  }
  params.set('page', String(page))
  return `/cari?${params.toString()}`
}

// Computed
const visiblePages = computed(() => {
  if (!hasPagination.value || !props.articles.data || props.articles.data.length === 0) {
    return []
  }
  
  const current = (props.articles as any).current_page ?? 1
  const total = (props.articles as any).last_page ?? 1
  
  if (total <= 1) return []
  
  const delta = 2
  const range = []
  const rangeWithDots: (number | string)[] = []

  for (let i = Math.max(2, current - delta); i <= Math.min(total - 1, current + delta); i++) {
    range.push(i)
  }

  if (current - delta > 2) {
    rangeWithDots.push(1, '...')
  } else {
    rangeWithDots.push(1)
  }

  rangeWithDots.push(...range)

  if (current + delta < total - 1) {
    rangeWithDots.push('...', total)
  } else if (total > 1) {
    rangeWithDots.push(total)
  }

  return rangeWithDots
})

const startItem = computed(() => {
  if (!hasPagination.value) return 0
  return (props.articles as any).from ?? 0
})

const endItem = computed(() => {
  if (!hasPagination.value) return 0
  return (props.articles as any).to ?? 0
})

// Methods
const performSearch = () => {
  if (searchQuery.value.trim()) {
    router.get('/cari', { q: searchQuery.value.trim() })
  }
}

const clearSearch = () => {
  searchQuery.value = ''
  router.get('/cari')
}

const formatDate = (dateString: string): string => {
  const date = new Date(dateString)
  const now = new Date()
  const diffInSeconds = Math.floor((now.getTime() - date.getTime()) / 1000)
  const diffInMinutes = Math.floor(diffInSeconds / 60)
  const diffInHours = Math.floor(diffInMinutes / 60)
  const diffInDays = Math.floor(diffInHours / 24)

  if (diffInMinutes < 1) {
    return 'Baru saja'
  } else if (diffInMinutes < 60) {
    return `${diffInMinutes} menit yang lalu`
  } else if (diffInHours < 24) {
    return `${diffInHours} jam yang lalu`
  } else if (diffInDays === 1) {
    return 'Kemarin'
  } else if (diffInDays < 7) {
    return `${diffInDays} hari yang lalu`
  } else {
    return date.toLocaleDateString('id-ID', {
      day: 'numeric',
      month: 'short',
      year: 'numeric'
    })
  }
}

// Lifecycle
onMounted(() => {
  // Focus on search input if no query
  if (!props.query) {
    const input = document.querySelector('input[type="search"]') as HTMLInputElement
    input?.focus()
  }
})
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
