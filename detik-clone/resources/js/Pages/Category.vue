<template>
  <Head>
    <title>{{ category.name }} - DetikClone</title>
    <meta name="description" :content="`Berita terkini kategori ${category.name}. Temukan informasi terbaru dan terupdate dari DetikClone.`">
  </Head>

  <AppLayout :categories="allCategories">
    <div class="container mx-auto px-4 py-6">
      <!-- Category Header -->
      <div class="mb-8">
        <nav class="text-sm text-gray-500 mb-4">
          <Link href="/" class="hover:text-detik-red">Beranda</Link>
          <span class="mx-2">/</span>
          <span class="text-gray-900">{{ category.name }}</span>
        </nav>
        
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ category.name }}</h1>
            <p v-if="category.description" class="text-gray-600">{{ category.description }}</p>
          </div>
          <div class="text-right">
            <span class="text-sm text-gray-500">{{ articles.total }} artikel</span>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-3">
          <!-- Articles Grid -->
          <div v-if="articles.data.length > 0" class="space-y-6">
            <!-- Featured Article (first article gets special treatment) -->
            <article 
              v-if="articles.current_page === 1 && articles.data[0]"
              class="group bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-lg transition-shadow mb-8"
            >
              <Link :href="`/articles/${articles.data[0].slug}`">
                <div class="relative">
                  <img 
                    :src="articles.data[0].featured_image || '/images/placeholder.jpg'"
                    :alt="articles.data[0].title"
                    class="w-full h-64 object-cover group-hover:scale-105 transition-transform duration-300"
                  >
                  <div class="absolute top-4 left-4">
                    <span class="bg-detik-red text-white px-3 py-1 rounded text-sm font-medium">
                      {{ articles.data[0].category.name }}
                    </span>
                  </div>
                </div>
                <div class="p-6">
                  <h2 class="text-2xl font-bold text-gray-900 group-hover:text-detik-red line-clamp-3 mb-3">
                    {{ articles.data[0].title }}
                  </h2>
                  <p class="text-gray-600 line-clamp-3 mb-4">
                    {{ articles.data[0].excerpt }}
                  </p>
                  <div class="flex items-center text-sm text-gray-500">
                    <span>{{ articles.data[0].author.name }}</span>
                    <span class="mx-2">•</span>
                    <time :datetime="articles.data[0].published_at">
                      {{ formatDate(articles.data[0].published_at) }}
                    </time>
                  </div>
                </div>
              </Link>
            </article>

            <!-- Regular Articles Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <article 
                v-for="article in (articles.current_page === 1 ? articles.data.slice(1) : articles.data)" 
                :key="article.id"
                class="group bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-md transition-shadow"
              >
                <Link :href="`/articles/${article.slug}`">
                  <div class="relative">
                    <img 
                      :src="article.featured_image || '/images/placeholder.jpg'"
                      :alt="article.title"
                      class="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-300"
                    >
                    <div class="absolute top-3 left-3">
                      <span class="bg-detik-red text-white px-2 py-1 rounded text-xs font-medium">
                        {{ article.category.name }}
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
                      <span>{{ article.author.name }}</span>
                      <time :datetime="article.published_at">
                        {{ formatDate(article.published_at) }}
                      </time>
                    </div>
                  </div>
                </Link>
              </article>
            </div>
          </div>

          <!-- Empty State -->
          <div v-else class="text-center py-12">
            <div class="mx-auto max-w-md">
              <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path>
              </svg>
              <h3 class="mt-2 text-sm font-medium text-gray-900">Belum ada artikel</h3>
              <p class="mt-1 text-sm text-gray-500">
                Belum ada artikel yang dipublikasikan dalam kategori {{ category.name }}.
              </p>
              <div class="mt-6">
                <Link href="/" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-detik-red hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-detik-red">
                  Kembali ke Beranda
                </Link>
              </div>
            </div>
          </div>

          <!-- Pagination -->
          <div v-if="articles.data.length > 0" class="mt-8">
            <nav class="flex items-center justify-between">
              <div class="flex-1 flex justify-between sm:hidden">
                <Link
                  v-if="articles.prev_page_url"
                  :href="articles.prev_page_url"
                  class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                >
                  Sebelumnya
                </Link>
                <Link
                  v-if="articles.next_page_url"
                  :href="articles.next_page_url"
                  class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                >
                  Selanjutnya
                </Link>
              </div>
              <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                  <p class="text-sm text-gray-700">
                    Menampilkan
                    {{ ' ' }}
                    <span class="font-medium">{{ articles.from || 0 }}</span>
                    {{ ' ' }}
                    sampai
                    {{ ' ' }}
                    <span class="font-medium">{{ articles.to || 0 }}</span>
                    {{ ' ' }}
                    dari
                    {{ ' ' }}
                    <span class="font-medium">{{ articles.total }}</span>
                    {{ ' ' }}
                    artikel
                  </p>
                </div>
                <div>
                  <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    <!-- Previous -->
                    <Link
                      v-if="articles.prev_page_url"
                      :href="articles.prev_page_url"
                      class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                    >
                      <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                      </svg>
                    </Link>
                    
                    <!-- Page Numbers -->
                    <template v-for="page in getPageNumbers()" :key="page">
                      <Link
                        v-if="page !== '...'"
                        :href="getPageUrl(page)"
                        :class="[
                          'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                          page === articles.current_page
                            ? 'z-10 bg-detik-red border-detik-red text-white'
                            : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                        ]"
                      >
                        {{ page }}
                      </Link>
                      <span
                        v-else
                        class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700"
                      >
                        ...
                      </span>
                    </template>
                    
                    <!-- Next -->
                    <Link
                      v-if="articles.next_page_url"
                      :href="articles.next_page_url"
                      class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                    >
                      <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                      </svg>
                    </Link>
                  </nav>
                </div>
              </div>
            </nav>
          </div>
        </div>

        <!-- Sidebar -->
        <aside class="lg:col-span-1">
          <!-- Other Categories -->
          <div class="bg-white border border-gray-200 rounded-lg p-6 mb-8">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Kategori Lainnya</h3>
            <div class="space-y-2">
              <Link 
                v-for="cat in otherCategories" 
                :key="cat.id"
                :href="`/kategori/${cat.slug}`"
                class="flex items-center justify-between p-2 rounded hover:bg-gray-50 group"
                :class="{ 'bg-detik-red bg-opacity-10': cat.id === category.id }"
              >
                <span 
                  class="text-gray-700 group-hover:text-detik-red"
                  :class="{ 'text-detik-red font-medium': cat.id === category.id }"
                >
                  {{ cat.name }}
                </span>
                <svg class="w-4 h-4 text-gray-400 group-hover:text-detik-red" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7"></path>
                </svg>
              </Link>
            </div>
          </div>

          <!-- Popular Articles -->
          <div class="bg-white border border-gray-200 rounded-lg p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Terpopuler</h3>
            <div class="space-y-4">
              <article 
                v-for="(article, index) in popularArticles" 
                :key="article.id"
                class="group flex space-x-3"
              >
                <div class="flex-shrink-0 w-6 h-6 bg-detik-red text-white rounded-full flex items-center justify-center text-xs font-bold">
                  {{ index + 1 }}
                </div>
                <div class="flex-1 min-w-0">
                  <Link :href="`/articles/${article.slug}`">
                    <h4 class="text-sm font-medium text-gray-900 group-hover:text-detik-red line-clamp-2">
                      {{ article.title }}
                    </h4>
                  </Link>
                  <div class="mt-1 text-xs text-gray-500">
                    <time :datetime="article.published_at">
                      {{ formatDate(article.published_at) }}
                    </time>
                  </div>
                </div>
              </article>
            </div>
          </div>
        </aside>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

interface User {
  id: number
  name: string
}

interface Category {
  id: number
  name: string
  slug: string
  description?: string
}

interface Article {
  id: number
  title: string
  slug: string
  excerpt: string
  featured_image?: string
  published_at: string
  author: User
  category: Category
}

interface PaginatedArticles {
  data: Article[]
  current_page: number
  total: number
  per_page: number
  last_page: number
  from: number | null
  to: number | null
  prev_page_url: string | null
  next_page_url: string | null
}

const props = defineProps<{
  category: Category
  articles: PaginatedArticles
  allCategories?: Category[]
  popularArticles?: Article[]
}>()

// Computed properties
const otherCategories = computed(() => {
  if (!props.allCategories) return []
  return props.allCategories.filter(cat => cat.id !== props.category.id)
})

// Utility functions
const formatDate = (dateString: string): string => {
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
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    })
  }
}

const getPageNumbers = (): (number | string)[] => {
  const current = props.articles.current_page
  const last = props.articles.last_page
  const pages: (number | string)[] = []
  
  if (last <= 7) {
    // Show all pages if there are 7 or fewer
    for (let i = 1; i <= last; i++) {
      pages.push(i)
    }
  } else {
    // Always show first page
    pages.push(1)
    
    if (current > 4) {
      pages.push('...')
    }
    
    // Show pages around current page
    const start = Math.max(2, current - 1)
    const end = Math.min(last - 1, current + 1)
    
    for (let i = start; i <= end; i++) {
      pages.push(i)
    }
    
    if (current < last - 3) {
      pages.push('...')
    }
    
    // Always show last page
    if (last > 1) {
      pages.push(last)
    }
  }
  
  return pages
}

const getPageUrl = (page: number): string => {
  const url = new URL(window.location.href)
  url.searchParams.set('page', page.toString())
  return url.pathname + url.search
}
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