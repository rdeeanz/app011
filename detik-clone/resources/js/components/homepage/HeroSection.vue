<template>
  <section class="hero-section bg-gradient-to-br from-red-600 to-red-800 text-white">
    <div class="container mx-auto px-4">
      <div v-if="loading" class="hero-loading">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 py-12">
          <div class="animate-pulse">
            <div class="h-8 bg-red-400 rounded mb-4 w-3/4"></div>
            <div class="h-4 bg-red-400 rounded mb-2 w-full"></div>
            <div class="h-4 bg-red-400 rounded mb-4 w-2/3"></div>
            <div class="h-10 bg-red-500 rounded w-1/3"></div>
          </div>
          <div class="h-64 bg-red-400 rounded animate-pulse"></div>
        </div>
      </div>

      <div v-else class="hero-content py-12">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
          
          <!-- Main Featured Article -->
          <div v-if="mainArticle" class="hero-text">
            <div class="flex items-center mb-4">
              <span class="bg-yellow-500 text-black px-3 py-1 rounded-full text-sm font-semibold mr-3">
                UTAMA
              </span>
              <CategoryBadge 
                :category="mainArticle.category"
                variant="light"
              />
            </div>

            <h1 class="hero-title text-3xl lg:text-4xl xl:text-5xl font-bold leading-tight mb-4">
              {{ mainArticle.title }}
            </h1>

            <p class="hero-excerpt text-lg lg:text-xl text-red-100 mb-6 leading-relaxed">
              {{ mainArticle.excerpt }}
            </p>

            <div class="hero-meta flex items-center justify-between mb-6">
              <div class="flex items-center space-x-4">
                <AuthorAvatar 
                  :author="mainArticle.author"
                  size="sm"
                />
                <div>
                  <p class="font-medium">{{ mainArticle.author.name }}</p>
                  <p class="text-red-200 text-sm">
                    {{ formatDate(mainArticle.published_at) }}
                  </p>
                </div>
              </div>

              <div class="flex items-center space-x-4 text-red-200">
                <div class="flex items-center">
                  <EyeIcon class="w-4 h-4 mr-1" />
                  <span class="text-sm">{{ formatNumber(mainArticle.views_count) }}</span>
                </div>
                <div class="flex items-center">
                  <ClockIcon class="w-4 h-4 mr-1" />
                  <span class="text-sm">{{ mainArticle.read_time }} min</span>
                </div>
              </div>
            </div>

            <button
              @click="$emit('article-click', mainArticle)"
              class="hero-cta bg-yellow-500 hover:bg-yellow-400 text-black font-semibold px-8 py-3 rounded-lg transition-colors duration-200 transform hover:scale-105"
            >
              Baca Selengkapnya
            </button>
          </div>

          <!-- Featured Image -->
          <div v-if="mainArticle" class="hero-image">
            <div class="relative overflow-hidden rounded-lg shadow-2xl">
              <LazyImage
                :src="mainArticle.featured_image"
                :alt="mainArticle.title"
                class="w-full h-64 lg:h-80 object-cover transform hover:scale-105 transition-transform duration-300"
                @click="$emit('article-click', mainArticle)"
              />
              
              <!-- Play button for video articles -->
              <div 
                v-if="mainArticle.content_type === 'video'"
                class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-30 hover:bg-opacity-20 transition-colors duration-200 cursor-pointer"
                @click="$emit('article-click', mainArticle)"
              >
                <PlayIcon class="w-16 h-16 text-white opacity-80 hover:opacity-100 transition-opacity" />
              </div>

              <!-- Article type badge -->
              <div class="absolute top-4 right-4">
                <ContentTypeBadge :type="mainArticle.content_type" />
              </div>
            </div>
          </div>

        </div>

        <!-- Secondary Featured Articles -->
        <div v-if="secondaryArticles.length > 0" class="mt-12">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <FeaturedCard
              v-for="article in secondaryArticles"
              :key="article.id"
              :article="article"
              variant="secondary"
              @click="$emit('article-click', article)"
            />
          </div>
        </div>

        <!-- Breaking News Preview -->
        <div v-if="hasBreakingNews" class="mt-8 text-center">
          <div class="inline-flex items-center bg-red-700 bg-opacity-50 rounded-full px-6 py-2">
            <span class="animate-pulse w-2 h-2 bg-yellow-500 rounded-full mr-3"></span>
            <span class="text-red-100 text-sm">Breaking News tersedia</span>
            <ChevronDownIcon class="w-4 h-4 ml-2 text-red-200" />
          </div>
        </div>
      </div>
    </div>

    <!-- Decorative elements -->
    <div class="hero-decoration absolute inset-0 overflow-hidden pointer-events-none">
      <div class="absolute -top-40 -right-40 w-80 h-80 bg-yellow-500 rounded-full opacity-10 animate-pulse"></div>
      <div class="absolute -bottom-20 -left-20 w-60 h-60 bg-white rounded-full opacity-5 animate-pulse delay-1000"></div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { 
  EyeIcon, 
  ClockIcon, 
  PlayIcon, 
  ChevronDownIcon 
} from '@heroicons/vue/24/outline'
import CategoryBadge from '../common/CategoryBadge.vue'
import AuthorAvatar from '../common/AuthorAvatar.vue'
import LazyImage from '../common/LazyImage.vue'
import ContentTypeBadge from '../common/ContentTypeBadge.vue'
import FeaturedCard from '../common/FeaturedCard.vue'
import { formatDate, formatNumber } from '@/utils/formatters'
import type { Article } from '@/types'

// Props
interface Props {
  featuredArticles: Article[]
  loading?: boolean
  hasBreakingNews?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  hasBreakingNews: false
})

// Emits
const emit = defineEmits<{
  'article-click': [article: Article]
}>()

// Computed
const mainArticle = computed(() => {
  return props.featuredArticles[0] || null
})

const secondaryArticles = computed(() => {
  return props.featuredArticles.slice(1, 4)
})
</script>

<style scoped>
.hero-section {
  position: relative;
  background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
  min-height: 500px;
}

.hero-title {
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
  line-height: 1.2;
}

.hero-excerpt {
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

.hero-image {
  cursor: pointer;
}

.hero-cta {
  box-shadow: 0 4px 14px 0 rgba(251, 191, 36, 0.3);
  transition: all 0.2s ease;
}

.hero-cta:hover {
  box-shadow: 0 6px 20px 0 rgba(251, 191, 36, 0.5);
}

.hero-decoration {
  z-index: 0;
}

.hero-content {
  position: relative;
  z-index: 1;
}

/* Responsive adjustments */
@media (max-width: 1023px) {
  .hero-section {
    min-height: 400px;
  }
  
  .hero-title {
    font-size: 2rem;
    line-height: 1.3;
  }
  
  .hero-excerpt {
    font-size: 1rem;
  }
}

@media (max-width: 767px) {
  .hero-section {
    min-height: 300px;
  }
  
  .hero-title {
    font-size: 1.75rem;
  }
  
  .hero-meta {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
}

/* Animation for featured cards */
.featured-card {
  transition: transform 0.2s ease;
}

.featured-card:hover {
  transform: translateY(-4px);
}

/* Loading animation */
.hero-loading {
  min-height: 400px;
  display: flex;
  align-items: center;
}

/* Pulse animation for breaking news indicator */
@keyframes pulse-glow {
  0%, 100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.7;
    transform: scale(1.1);
  }
}

.animate-pulse-glow {
  animation: pulse-glow 2s ease-in-out infinite;
}
</style>