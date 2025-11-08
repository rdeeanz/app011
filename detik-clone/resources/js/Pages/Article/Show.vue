<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
      <div class="max-w-6xl mx-auto px-4 py-3">
        <nav class="flex items-center space-x-4">
          <Link href="/" class="text-red-600 hover:text-red-700 font-bold text-xl">
            DetikClone
          </Link>
          <div class="text-gray-400">|</div>
          <Link 
            :href="`/kategori/${article.category.slug}`" 
            class="text-blue-600 hover:text-blue-700 capitalize"
          >
            {{ article.category.name }}
          </Link>
        </nav>
      </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-6xl mx-auto px-4 py-8">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Article Content -->
        <article class="lg:col-span-2">
          <!-- Article Header -->
          <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <!-- Category Badge -->
            <div class="mb-4">
              <span 
                class="inline-block px-3 py-1 text-sm font-medium text-white rounded-full"
                :style="{ backgroundColor: article.category.color }"
              >
                {{ article.category.name }}
              </span>
              <span v-if="article.is_breaking" class="ml-2 inline-block px-3 py-1 text-sm font-medium bg-red-600 text-white rounded-full">
                BREAKING
              </span>
              <span v-if="article.is_featured" class="ml-2 inline-block px-3 py-1 text-sm font-medium bg-yellow-500 text-white rounded-full">
                FEATURED
              </span>
            </div>

            <!-- Article Title -->
            <h1 class="text-3xl font-bold text-gray-900 mb-4 leading-tight">
              {{ article.title }}
            </h1>

            <!-- Article Meta -->
            <div class="flex items-center text-sm text-gray-500 mb-6">
              <div class="flex items-center">
                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                </svg>
                <span>{{ article.author.name }}</span>
              </div>
              <span class="mx-2">•</span>
              <div class="flex items-center">
                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                </svg>
                <span>{{ formatDate(article.published_at) }}</span>
              </div>
              <span class="mx-2">•</span>
              <div class="flex items-center">
                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                  <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                </svg>
                <span>{{ article.views_count }} views</span>
              </div>
              <span class="mx-2">•</span>
              <div class="flex items-center">
                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
                <span>{{ article.reading_time }} min read</span>
              </div>
            </div>

            <!-- Featured Image -->
            <div v-if="article.featured_image_url || article.featured_image" class="mb-6">
              <img 
                :src="article.featured_image_url || article.featured_image" 
                :alt="article.title"
                class="w-full h-auto object-cover rounded-lg"
                onerror="this.onerror=null; this.src='/images/placeholder.jpg';"
              >
            </div>

            <!-- Article Excerpt -->
            <div v-if="article.excerpt" class="text-lg text-gray-700 mb-6 font-medium border-l-4 border-blue-500 pl-4 italic">
              {{ article.excerpt }}
            </div>
          </div>

          <!-- Article Body -->
          <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <div 
              class="prose prose-lg max-w-none"
              v-html="article.content"
            ></div>
          </div>

          <!-- Article Footer -->
          <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <!-- Tags -->
            <div v-if="article.tags && article.tags.length > 0" class="mb-4">
              <h3 class="text-sm font-medium text-gray-500 mb-2">Tags:</h3>
              <div class="flex flex-wrap gap-2">
                <span 
                  v-for="tag in article.tags" 
                  :key="tag.id"
                  class="inline-block px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded-full hover:bg-gray-200 cursor-pointer"
                >
                  {{ tag.name }}
                </span>
              </div>
            </div>

            <!-- Share Buttons -->
            <div class="border-t pt-4">
              <h3 class="text-sm font-medium text-gray-500 mb-3">Share this article:</h3>
              <div class="flex space-x-3">
                <button 
                  @click="shareOnFacebook"
                  class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700"
                >
                  <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                  </svg>
                  Facebook
                </button>
                <button 
                  @click="shareOnTwitter"
                  class="inline-flex items-center px-4 py-2 bg-blue-400 text-white text-sm font-medium rounded-md hover:bg-blue-500"
                >
                  <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                  </svg>
                  Twitter
                </button>
                <button 
                  @click="shareOnWhatsApp"
                  class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700"
                >
                  <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.488"/>
                  </svg>
                  WhatsApp
                </button>
              </div>
            </div>
          </div>
        </article>

        <!-- Sidebar -->
        <aside class="space-y-6">
          <!-- Related Articles -->
          <div v-if="relatedArticles && relatedArticles.length > 0" class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Related Articles</h3>
            <div class="space-y-4">
              <div 
                v-for="related in relatedArticles" 
                :key="related.id"
                class="flex space-x-3"
              >
                <img 
                  v-if="related.featured_image"
                  :src="related.featured_image_url || `/storage/${related.featured_image}`"
                  :alt="related.title"
                  class="w-16 h-16 object-cover rounded"
                >
                <div class="flex-1">
                  <Link 
                    :href="`/articles/${related.slug}`"
                    class="text-sm font-medium text-gray-900 hover:text-blue-600 line-clamp-2"
                  >
                    {{ related.title }}
                  </Link>
                  <p class="text-xs text-gray-500 mt-1">
                    {{ formatDate(related.published_at) }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- More from Author -->
          <div v-if="moreFromAuthor && moreFromAuthor.length > 0" class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">More from {{ article.author.name }}</h3>
            <div class="space-y-4">
              <div 
                v-for="more in moreFromAuthor" 
                :key="more.id"
                class="flex space-x-3"
              >
                <img 
                  v-if="more.featured_image"
                  :src="more.featured_image_url || `/storage/${more.featured_image}`"
                  :alt="more.title"
                  class="w-16 h-16 object-cover rounded"
                >
                <div class="flex-1">
                  <Link 
                    :href="`/articles/${more.slug}`"
                    class="text-sm font-medium text-gray-900 hover:text-blue-600 line-clamp-2"
                  >
                    {{ more.title }}
                  </Link>
                  <p class="text-xs text-gray-500 mt-1">
                    {{ formatDate(more.published_at) }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </aside>
      </div>
    </main>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'

// Props
const props = defineProps({
  article: Object,
  relatedArticles: Array,
  moreFromAuthor: Array,
  comments: Object,
  commentStats: Object,
})

// Methods
const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('id-ID', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const shareOnFacebook = () => {
  const url = encodeURIComponent(window.location.href)
  const title = encodeURIComponent(props.article.title)
  window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`, '_blank', 'width=600,height=400')
}

const shareOnTwitter = () => {
  const url = encodeURIComponent(window.location.href)
  const title = encodeURIComponent(props.article.title)
  window.open(`https://twitter.com/intent/tweet?url=${url}&text=${title}`, '_blank', 'width=600,height=400')
}

const shareOnWhatsApp = () => {
  const url = encodeURIComponent(window.location.href)
  const title = encodeURIComponent(props.article.title)
  window.open(`https://wa.me/?text=${title} ${url}`, '_blank')
}
</script>

<style>
/* Custom styles for prose content */
.prose {
  @apply text-gray-700;
}

.prose h1, .prose h2, .prose h3, .prose h4, .prose h5, .prose h6 {
  @apply text-gray-900 font-bold;
}

.prose h1 { @apply text-2xl mb-4; }
.prose h2 { @apply text-xl mb-3; }
.prose h3 { @apply text-lg mb-2; }

.prose p {
  @apply mb-4 leading-relaxed;
}

.prose ul, .prose ol {
  @apply mb-4 pl-6;
}

.prose li {
  @apply mb-1;
}

.prose blockquote {
  @apply border-l-4 border-gray-300 pl-4 italic text-gray-600 mb-4;
}

.prose a {
  @apply text-blue-600 hover:text-blue-700 underline;
}

.prose img {
  @apply rounded-lg mb-4 w-full h-auto;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>