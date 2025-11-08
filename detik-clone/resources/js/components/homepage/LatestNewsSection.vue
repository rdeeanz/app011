<template>
  <section class="latest-news-section">
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-2xl font-bold text-gray-900">Berita Terbaru</h2>
      <Link 
        href="/berita-terbaru" 
        class="text-detik-red hover:underline font-medium"
      >
        Lihat Semua
      </Link>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div 
        v-for="n in itemsPerPage" 
        :key="n"
        class="animate-pulse bg-white border border-gray-200 rounded-lg overflow-hidden"
      >
        <div class="w-full h-48 bg-gray-300"></div>
        <div class="p-4">
          <div class="h-4 bg-gray-300 rounded mb-2"></div>
          <div class="h-4 bg-gray-300 rounded w-3/4 mb-3"></div>
          <div class="flex justify-between">
            <div class="h-3 bg-gray-300 rounded w-1/4"></div>
            <div class="h-3 bg-gray-300 rounded w-1/4"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- News Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <article 
        v-for="article in currentPageArticles" 
        :key="article.id"
        class="group bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-md transition-shadow"
      >
        <Link :href="`/articles/${article.slug}`">
          <div class="relative">
            <img 
              :src="article.featured_image || '/images/placeholder.jpg'"
              :alt="article.title"
              class="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-300"
              loading="lazy"
            >
            <div class="absolute top-3 left-3">
              <span 
                class="text-white px-2 py-1 rounded text-xs font-medium"
                :style="{ backgroundColor: article.category?.color || '#dc2626' }"
              >
                {{ article.category?.name || 'News' }}
              </span>
            </div>
          </div>
          <div class="p-4">
            <h3 class="font-semibold text-gray-900 group-hover:text-detik-red line-clamp-3 mb-2">
              {{ article.title }}
            </h3>
            <p class="text-gray-600 text-sm line-clamp-2 mb-3">
              {{ article.excerpt }}
            </p>
            <div class="flex items-center justify-between text-xs text-gray-500">
              <span>{{ article.author?.name || 'Redaksi' }}</span>
              <time :datetime="article.published_at">
                {{ formatDate(article.published_at) }}
              </time>
            </div>
          </div>
        </Link>
      </article>
    </div>

    <!-- Pagination -->
    <div v-if="!loading && totalPages > 1" class="mt-8">
      <nav class="flex items-center justify-center space-x-2" aria-label="Pagination">
        <!-- Previous Button -->
        <button
          @click="goToPage(currentPage - 1)"
          :disabled="currentPage === 1"
          :class="[
            'px-3 py-2 text-sm font-medium rounded-md transition-colors',
            currentPage === 1 
              ? 'text-gray-400 cursor-not-allowed' 
              : 'text-gray-700 hover:bg-gray-100'
          ]"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </button>

        <!-- Page Numbers -->
        <template v-for="page in visiblePages" :key="page">
          <button
            v-if="page !== '...'"
            @click="goToPage(Number(page))"
            :class="[
              'px-3 py-2 text-sm font-medium rounded-md transition-colors',
              page === currentPage
                ? 'bg-detik-red text-white'
                : 'text-gray-700 hover:bg-gray-100'
            ]"
          >
            {{ page }}
          </button>
          <span v-else class="px-3 py-2 text-sm text-gray-500">...</span>
        </template>

        <!-- Next Button -->
        <button
          @click="goToPage(currentPage + 1)"
          :disabled="currentPage === totalPages"
          :class="[
            'px-3 py-2 text-sm font-medium rounded-md transition-colors',
            currentPage === totalPages 
              ? 'text-gray-400 cursor-not-allowed' 
              : 'text-gray-700 hover:bg-gray-100'
          ]"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </button>
      </nav>

      <!-- Pagination Info -->
      <div class="text-center mt-4 text-sm text-gray-600">
        Menampilkan {{ startItem }} - {{ endItem }} dari {{ totalArticles }} berita
      </div>
    </div>

    <!-- Load More Button (Alternative to pagination) -->
    <div v-if="showLoadMore && hasMorePages" class="mt-8 text-center">
      <button
        @click="loadMoreArticles"
        :disabled="loadingMore"
        class="px-6 py-3 bg-detik-red text-white font-medium rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <span v-if="!loadingMore">Muat Lebih Banyak</span>
        <span v-else class="flex items-center">
          <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          Memuat...
        </span>
      </button>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Link } from '@inertiajs/vue3'

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

interface Props {
  initialArticles?: Article[]
  itemsPerPage?: number
  showLoadMore?: boolean
  enableInfiniteScroll?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  initialArticles: () => [],
  itemsPerPage: 6,
  showLoadMore: false,
  enableInfiniteScroll: false
})

// Reactive state
const articles = ref<Article[]>([])
const loading = ref(true)
const loadingMore = ref(false)
const currentPage = ref(1)
const totalArticles = ref(0)
const hasMorePages = ref(true)

// Scroll handler for infinite scroll (declared in outer scope)
let handleScroll: (() => void) | null = null

// Computed properties
const totalPages = computed(() => Math.ceil(totalArticles.value / props.itemsPerPage))

const currentPageArticles = computed(() => {
  if (props.showLoadMore) {
    return articles.value
  }
  
  const start = (currentPage.value - 1) * props.itemsPerPage
  const end = start + props.itemsPerPage
  return articles.value.slice(start, end)
})

const startItem = computed(() => {
  return ((currentPage.value - 1) * props.itemsPerPage) + 1
})

const endItem = computed(() => {
  const end = currentPage.value * props.itemsPerPage
  return Math.min(end, totalArticles.value)
})

const visiblePages = computed(() => {
  const total = totalPages.value
  const current = currentPage.value
  const delta = 2
  const range = []
  const rangeWithDots = []

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

// Methods
const fetchLatestNews = async (page: number = 1) => {
  try {
    loading.value = true
    
    const response = await fetch(`/berita-terbaru/homepage?page=${page}&per_page=${props.itemsPerPage}`)
    
    // Check HTTP status before parsing JSON
    if (!response.ok) {
      let errorMessage = `HTTP ${response.status}: ${response.statusText}`
      try {
        const errorData = await response.text()
        if (errorData) {
          errorMessage += ` - ${errorData}`
        }
      } catch (e) {
        // Ignore error parsing error response
      }
      throw new Error(errorMessage)
    }
    
    const data = await response.json()
    
    if (data.success) {
      if (page === 1 || !props.showLoadMore) {
        articles.value = data.data
      } else {
        articles.value.push(...data.data)
      }
      
      totalArticles.value = data.total
      hasMorePages.value = data.pagination ? data.pagination.has_more_pages : false
    } else {
      throw new Error(data.message || 'Failed to fetch latest news')
    }
  } catch (error) {
    console.error('Failed to fetch latest news:', error)
    // Optionally show user-friendly error message
  } finally {
    loading.value = false
  }
}

const goToPage = (page: number) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page
    
    if (!props.showLoadMore) {
      fetchLatestNews(page)
    }
    
    // Scroll to top of section
    document.querySelector('.latest-news-section')?.scrollIntoView({ 
      behavior: 'smooth', 
      block: 'start' 
    })
  }
}

const loadMoreArticles = async () => {
  try {
    loadingMore.value = true
    
    // Get CSRF token and validate it exists
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    if (!csrfToken) {
      console.error('CSRF token not found in meta tag')
      loadingMore.value = false
      return
    }
    
    const response = await fetch('/berita-terbaru/load-more', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken
      },
      body: JSON.stringify({
        offset: articles.value.length,
        limit: props.itemsPerPage
      })
    })
    
    // Check HTTP status before parsing JSON
    if (!response.ok) {
      let errorMessage = `HTTP ${response.status}: ${response.statusText}`
      try {
        const errorText = await response.text()
        if (errorText) {
          errorMessage += ` - ${errorText}`
        }
      } catch (e) {
        // Ignore error parsing error response
      }
      throw new Error(errorMessage)
    }
    
    const data = await response.json()
    
    if (data.success) {
      articles.value.push(...data.data)
      hasMorePages.value = data.has_more
    } else {
      throw new Error(data.message || 'Failed to load more articles')
    }
  } catch (error) {
    console.error('Failed to load more articles:', error)
    // Optionally show user-friendly error message
  } finally {
    loadingMore.value = false
  }
}

const formatDate = (dateString: string) => {
  const date = new Date(dateString)
  return new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }).format(date)
}

// Lifecycle hooks
onMounted(() => {
  // Always fetch latest data from API to ensure proper pagination
  fetchLatestNews()
  
  // Setup infinite scroll if enabled
  if (props.enableInfiniteScroll) {
    handleScroll = () => {
      const scrollTop = window.pageYOffset || document.documentElement.scrollTop
      const scrollHeight = document.documentElement.scrollHeight
      const clientHeight = document.documentElement.clientHeight
      
      if (scrollTop + clientHeight >= scrollHeight - 1000 && hasMorePages.value && !loadingMore.value) {
        loadMoreArticles()
      }
    }
    
    window.addEventListener('scroll', handleScroll)
  }
})

onUnmounted(() => {
  if (handleScroll) {
    window.removeEventListener('scroll', handleScroll)
  }
})

// No need to watch initialArticles anymore since we always fetch from API
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  line-clamp: 2;
}

.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
  line-clamp: 3;
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: .5;
  }
}
</style>