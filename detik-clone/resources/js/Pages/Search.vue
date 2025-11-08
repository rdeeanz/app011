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
        
        <!-- Search Info -->
        <div v-if="query" class="mt-4 text-gray-600" role="status" aria-live="polite">
          <p>
            Menampilkan hasil untuk: <strong class="text-gray-900">"{{ query }}"</strong>
            <span v-if="hasResults" class="ml-2">
              ({{ (articles as PaginatedArticles).total }} {{ (articles as PaginatedArticles).total === 1 ? 'hasil' : 'hasil' }} ditemukan)
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
              :aria-labelledby="`article-title-${article.id}`"
            >
              <!-- Image -->
              <div class="relative h-48 overflow-hidden">
                <Link :href="`/articles/${article.slug}`" :aria-label="`Lihat artikel: ${article.title}`">
                  <img 
                    :src="article.featured_image || '/images/placeholder.jpg'"
                    :alt="article.title"
                    class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
                    loading="lazy"
                  >
                </Link>
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
                <!-- Title with keyword highlighting -->
                <h3 
                  :id="`article-title-${article.id}`"
                  class="font-bold text-gray-900 group-hover:text-detik-red line-clamp-2 mb-2 text-lg"
                  v-html="highlightKeyword(article.title, query || '')"
                />
                
                <!-- Summary with 2-3 sentences -->
                <p class="text-gray-600 text-sm line-clamp-3 mb-3">
                  {{ getSummary(article.excerpt) }}
                </p>
                
                <!-- Meta -->
                <div class="flex items-center justify-between text-xs text-gray-500 mb-3">
                  <div class="flex items-center space-x-2">
                    <span>{{ article.author?.name || 'Redaksi' }}</span>
                  </div>
                  <time :datetime="article.published_at">
                    {{ formatDate(article.published_at) }}
                  </time>
                </div>

                <!-- Read More Link -->
                <Link 
                  :href="`/articles/${article.slug}`"
                  class="inline-flex items-center text-sm font-medium text-detik-red hover:text-red-700 transition-colors"
                  :aria-label="`Baca selengkapnya: ${article.title}`"
                >
                  Baca selengkapnya
                  <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                  </svg>
                </Link>
              </div>
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
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
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

// Check if we have results
const hasResults = computed(() => {
  return props.articles && 
         Array.isArray(props.articles.data) && 
         props.articles.data.length > 0
})

// Type guard to check if articles has pagination fields
const hasPagination = computed(() => {
  return props.articles 
    && typeof (props.articles as any).last_page === 'number' 
    && typeof (props.articles as any).current_page === 'number'
})

// Highlight keyword in text
const highlightKeyword = (text: string, keyword: string): string => {
  if (!keyword || !text) return text
  
  // Escape special regex characters and normalize
  const escapedKeyword = keyword
    .trim()
    .replace(/\s+/g, ' ')
    .replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
  
  // Case-insensitive, Unicode-aware regex
  const regex = new RegExp(`(${escapedKeyword})`, 'gi')
  
  return text.replace(regex, '<mark class="bg-yellow-200 text-gray-900 font-medium px-1 rounded">$1</mark>')
}

// Format date - DD MMM YYYY
const formatDate = (dateString: string): string => {
  const date = new Date(dateString)
  
  return date.toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  })
}

// Extract first 2-3 sentences from excerpt or content
const getSummary = (text: string, maxSentences: number = 3): string => {
  if (!text) return ''
  
  // Split by sentence endings
  const sentences = text.match(/[^.!?]+[.!?]+/g) || [text]
  
  // Take first maxSentences
  const summary = sentences.slice(0, maxSentences).join(' ')
  
  // Limit to reasonable length
  if (summary.length > 200) {
    return summary.substring(0, 197) + '...'
  }
  
  return summary
}

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
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Ensure mark elements work properly in v-html */
:deep(mark) {
  background-color: #fef08a;
  color: #111827;
  font-weight: 500;
  padding: 0 0.25rem;
  border-radius: 0.125rem;
}
</style>
