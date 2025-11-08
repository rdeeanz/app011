<template>
  <Head title="Berita Terbaru" />
  
  <AppLayout>
    <div class="container mx-auto px-4 py-8">
      <!-- Page Header -->
      <div class="text-center mb-8">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
          Berita Terbaru
        </h1>
        <p class="text-lg text-gray-600 max-w-2xl mx-auto">
          Dapatkan informasi berita terbaru dan terkini dari berbagai kategori. 
          Update setiap hari dengan berita akurat dan terpercaya.
        </p>
      </div>

      <!-- Breadcrumb -->
      <nav class="flex mb-8" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
          <li class="inline-flex items-center">
            <Link href="/" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-detik-red">
              <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
              </svg>
              Beranda
            </Link>
          </li>
          <li>
            <div class="flex items-center">
              <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
              </svg>
              <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">Berita Terbaru</span>
            </div>
          </li>
        </ol>
      </nav>

      <!-- Loading State -->
      <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div 
          v-for="n in 12" 
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

      <!-- Latest News Grid -->
      <div v-else-if="paginatedNews.data.length > 0" class="space-y-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <article 
            v-for="article in paginatedNews.data" 
            :key="article.id"
            class="group bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-lg transition-all duration-300"
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
                <div class="absolute bottom-3 right-3">
                  <span class="bg-black bg-opacity-50 text-white px-2 py-1 rounded text-xs">
                    {{ article.views_count || 0 }} views
                  </span>
                </div>
              </div>
              <div class="p-4">
                <h2 class="font-semibold text-gray-900 group-hover:text-detik-red line-clamp-2 mb-2 text-lg">
                  {{ article.title }}
                </h2>
                <p class="text-gray-600 text-sm line-clamp-3 mb-3">
                  {{ article.excerpt }}
                </p>
                <div class="flex items-center justify-between text-xs text-gray-500">
                  <div class="flex items-center space-x-2">
                    <img 
                      v-if="article.author?.avatar"
                      :src="article.author.avatar" 
                      :alt="article.author.name"
                      class="w-4 h-4 rounded-full"
                    >
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
        <div class="flex flex-col items-center space-y-4">
          <!-- Pagination Controls -->
          <nav class="flex items-center justify-center space-x-2" aria-label="Pagination">
            <!-- Previous Button -->
            <Link
              v-if="paginatedNews.prev_page_url"
              :href="paginatedNews.prev_page_url"
              class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
              </svg>
            </Link>

            <!-- Page Numbers -->
            <template v-for="page in visiblePages" :key="page">
              <Link
                v-if="page !== '...'"
                :href="`/berita-terbaru?page=${page}`"
                :class="[
                  'px-3 py-2 text-sm font-medium rounded-md transition-colors',
                  page === paginatedNews.current_page
                    ? 'bg-detik-red text-white'
                    : 'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50'
                ]"
              >
                {{ page }}
              </Link>
              <span v-else class="px-3 py-2 text-sm text-gray-500">...</span>
            </template>

            <!-- Next Button -->
            <Link
              v-if="paginatedNews.next_page_url"
              :href="paginatedNews.next_page_url"  
              class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </Link>
          </nav>

          <!-- Pagination Info -->
          <div class="text-sm text-gray-600">
            Menampilkan {{ startItem }} - {{ endItem }} dari {{ paginatedNews.total }} berita
          </div>

          <!-- Items per page selector -->
          <div class="flex items-center space-x-2">
            <span class="text-sm text-gray-600">Tampilkan:</span>
            <select 
              v-model="itemsPerPage"
              @change="changeItemsPerPage"
              class="px-3 py-1 border border-gray-300 rounded-md text-sm focus:ring-detik-red focus:border-detik-red"
            >
              <option value="10">10</option>
              <option value="15">15</option>
              <option value="20">20</option>
            </select>
            <span class="text-sm text-gray-600">per halaman</span>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4h4.01" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada berita</h3>
        <p class="mt-1 text-sm text-gray-500">Belum ada berita yang dipublikasikan.</p>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

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

interface PaginatedData {
  data: Article[]
  current_page: number
  per_page: number
  total: number
  last_page: number
  next_page_url?: string
  prev_page_url?: string
}

interface Props {
  latestNews: PaginatedData
}

const props = defineProps<Props>()

// Reactive state
const loading = ref(false)
const itemsPerPage = ref(10)
const paginatedNews = ref<PaginatedData>(props.latestNews)

// Computed properties
const startItem = computed(() => {
  return ((paginatedNews.value.current_page - 1) * paginatedNews.value.per_page) + 1
})

const endItem = computed(() => {
  const end = paginatedNews.value.current_page * paginatedNews.value.per_page
  return Math.min(end, paginatedNews.value.total)
})

const visiblePages = computed(() => {
  const total = paginatedNews.value.last_page
  const current = paginatedNews.value.current_page
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
const formatDate = (dateString: string) => {
  const date = new Date(dateString)
  const now = new Date()
  const diffInHours = Math.floor((now.getTime() - date.getTime()) / (1000 * 60 * 60))
  
  if (diffInHours < 1) {
    const diffInMinutes = Math.floor((now.getTime() - date.getTime()) / (1000 * 60))
    return `${diffInMinutes} menit yang lalu`
  } else if (diffInHours < 24) {
    return `${diffInHours} jam yang lalu`
  } else {
    return date.toLocaleDateString('id-ID', {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  }
}

const changeItemsPerPage = () => {
  const url = new URL(window.location.href)
  url.searchParams.set('per_page', itemsPerPage.value.toString())
  url.searchParams.set('page', '1') // Reset to first page
  
  router.get(url.toString())
}

// Lifecycle
onMounted(() => {
  // Set items per page from URL if present
  const urlParams = new URLSearchParams(window.location.search)
  const perPageParam = urlParams.get('per_page')
  if (perPageParam) {
    itemsPerPage.value = parseInt(perPageParam, 10)
  }
})
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