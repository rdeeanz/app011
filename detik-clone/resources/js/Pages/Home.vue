<template>
  <Head>
    <title>DetikClone - Portal Berita Terdepan Indonesia</title>
    <meta name="description" content="Berita terkini Indonesia dan dunia. Informasi politik, ekonomi, olahraga, teknologi, dan lifestyle terupdate setiap hari.">
  </Head>

  <AppLayout :categories="categories">
    <div class="container mx-auto px-4 py-6">
      <!-- Breaking News Banner (if any) -->
      <div v-if="breakingNews.length > 0" class="mb-8">
        <div class="bg-detik-red text-white p-4 rounded-lg">
          <div class="flex items-center mb-2">
            <span class="bg-white text-detik-red px-2 py-1 rounded text-sm font-bold mr-3">
              BREAKING NEWS
            </span>
            <div class="flex-1 overflow-hidden">
              <div class="animate-marquee whitespace-nowrap">
                <span v-for="(news, index) in breakingNews" :key="news.id">
                  <Link 
                    :href="`/articles/${news.slug}`"
                    class="hover:underline"
                  >
                    {{ news.title }}
                  </Link>
                  <span v-if="index < breakingNews.length - 1" class="mx-4">•</span>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-3">
          <!-- Featured Articles -->
          <section class="mb-12">
            <div v-if="featuredArticles.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Main Featured Article -->
              <div class="md:col-span-1">
                <article class="group">
                  <Link :href="`/articles/${featuredArticles[0].slug}`">
                    <div class="relative overflow-hidden rounded-lg mb-4">
                      <img 
                        :src="featuredArticles[0].featured_image || '/images/placeholder.jpg'"
                        :alt="featuredArticles[0].title"
                        class="w-full h-64 object-cover group-hover:scale-105 transition-transform duration-300"
                      >
                      <div class="absolute top-4 left-4">
                        <span class="bg-detik-red text-white px-3 py-1 rounded text-sm font-medium">
                          {{ featuredArticles[0].category.name }}
                        </span>
                      </div>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 group-hover:text-detik-red line-clamp-3 mb-2">
                      {{ featuredArticles[0].title }}
                    </h1>
                    <p class="text-gray-600 line-clamp-2 mb-3">
                      {{ featuredArticles[0].excerpt }}
                    </p>
                    <div class="flex items-center text-sm text-gray-500">
                      <span>{{ featuredArticles[0].author.name }}</span>
                      <span class="mx-2">•</span>
                      <time :datetime="featuredArticles[0].published_at">
                        {{ formatDate(featuredArticles[0].published_at) }}
                      </time>
                    </div>
                  </Link>
                </article>
              </div>

              <!-- Secondary Featured Articles -->
              <div class="space-y-6">
                <article 
                  v-for="article in featuredArticles.slice(1, 5)" 
                  :key="article.id"
                  class="group flex space-x-4"
                >
                  <Link 
                    :href="`/articles/${article.slug}`"
                    class="flex-shrink-0"
                  >
                    <img 
                      :src="article.featured_image || '/images/placeholder.jpg'"
                      :alt="article.title"
                      class="w-24 h-20 object-cover rounded group-hover:opacity-80 transition-opacity"
                    >
                  </Link>
                  <div class="flex-1 min-w-0">
                    <div class="mb-2">
                      <span class="inline-block bg-gray-100 text-gray-700 px-2 py-1 rounded text-xs font-medium">
                        {{ article.category.name }}
                      </span>
                    </div>
                    <Link :href="`/articles/${article.slug}`">
                      <h3 class="font-semibold text-gray-900 group-hover:text-detik-red line-clamp-2 mb-1">
                        {{ article.title }}
                      </h3>
                    </Link>
                    <div class="flex items-center text-xs text-gray-500">
                      <span>{{ article.author.name }}</span>
                      <span class="mx-1">•</span>
                      <time :datetime="article.published_at">
                        {{ formatDate(article.published_at) }}
                      </time>
                    </div>
                  </div>
                </article>
              </div>
            </div>
          </section>

          <!-- Latest News -->
          <section>
            <div class="flex items-center justify-between mb-6">
              <h2 class="text-2xl font-bold text-gray-900">Berita Terbaru</h2>
              <Link href="/kategori/news" class="text-detik-red hover:underline font-medium">
                Lihat Semua
              </Link>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <article 
                v-for="article in latestNews" 
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
          </section>
        </div>

        <!-- Sidebar -->
        <aside class="lg:col-span-1">
          <!-- Popular Articles -->
          <div class="bg-white border border-gray-200 rounded-lg p-6 mb-8">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Terpopuler</h3>
            <div class="space-y-4">
              <article 
                v-for="(article, index) in latestNews.slice(0, 5)" 
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

          <!-- Categories -->
          <div class="bg-white border border-gray-200 rounded-lg p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Kategori</h3>
            <div class="space-y-2">
              <Link 
                v-for="category in categories" 
                :key="category.id"
                :href="`/kategori/${category.slug}`"
                class="flex items-center justify-between p-2 rounded hover:bg-gray-50 group"
              >
                <span class="text-gray-700 group-hover:text-detik-red">{{ category.name }}</span>
                <svg class="w-4 h-4 text-gray-400 group-hover:text-detik-red" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7"></path>
                </svg>
              </Link>
            </div>
          </div>
        </aside>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
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

defineProps<{
  featuredArticles: Article[]
  breakingNews: Article[]
  latestNews: Article[]
  categories: Category[]
}>()

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

@keyframes marquee {
  0% { transform: translateX(100%); }
  100% { transform: translateX(-100%); }
}

.animate-marquee {
  animation: marquee 20s linear infinite;
}
</style>