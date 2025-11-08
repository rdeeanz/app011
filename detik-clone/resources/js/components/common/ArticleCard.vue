<template>
  <article 
    class="article-card group cursor-pointer"
    :class="cardClasses"
    @click="$emit('click', article)"
    @keydown.enter="$emit('click', article)"
    @keydown.space.prevent="$emit('click', article)"
    tabindex="0"
    role="button"
    :aria-label="`Read article: ${article.title}`"
  >
    
    <!-- Article Image -->
    <div class="article-image relative overflow-hidden">
      <LazyImage
        :src="article.featured_image || '/images/placeholder-article.jpg'"
        :alt="article.title"
        :class="imageClasses"
        loading="lazy"
      />
      
      <!-- Image Overlay -->
      <div class="image-overlay absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300" />
      
      <!-- Content Type Badge -->
      <div class="absolute top-3 left-3">
        <ContentTypeBadge 
          :type="article.content_type"
          size="sm"
        />
      </div>
      
      <!-- Bookmark Button -->
      <div class="absolute top-3 right-3">
        <BookmarkButton
          :is-bookmarked="isBookmarked"
          :loading="bookmarkLoading"
          size="sm"
          variant="overlay"
          @click.stop="handleBookmark"
        />
      </div>
      
      <!-- Reading Time -->
      <div v-if="showReadTime && article.read_time" class="absolute bottom-3 right-3">
        <div class="bg-black/70 text-white text-xs px-2 py-1 rounded-full flex items-center">
          <ClockIcon class="w-3 h-3 mr-1" />
          {{ article.read_time }} min
        </div>
      </div>
    </div>

    <!-- Article Content -->
    <div class="article-content p-4">
      
      <!-- Category -->
      <div v-if="showCategory && article.category" class="article-category mb-2">
        <CategoryBadge
          :category="article.category"
          size="sm"
          clickable
          @click.stop="$emit('category-click', article)"
        />
      </div>

      <!-- Title -->
      <h3 class="article-title font-bold text-gray-900 mb-2 line-clamp-2 group-hover:text-red-600 transition-colors duration-200">
        {{ article.title }}
      </h3>

      <!-- Excerpt -->
      <p 
        v-if="showExcerpt && article.excerpt" 
        class="article-excerpt text-gray-600 text-sm mb-3 line-clamp-2"
      >
        {{ article.excerpt }}
      </p>

      <!-- Article Meta -->
      <div class="article-meta flex items-center justify-between text-xs text-gray-500">
        
        <!-- Author & Date -->
        <div class="flex items-center space-x-2">
          <div v-if="showAuthor && article.author" class="flex items-center">
            <AuthorAvatar
              :author="article.author"
              size="xs"
              clickable
              @click.stop="$emit('author-click', article)"
            />
            <span 
              class="ml-1 hover:text-red-600 cursor-pointer transition-colors duration-200"
              @click.stop="$emit('author-click', article)"
            >
              {{ article.author.name }}
            </span>
          </div>
          
          <span class="text-gray-400">•</span>
          
          <time :datetime="article.published_at">
            {{ formatRelativeTime(article.published_at) }}
          </time>
        </div>

        <!-- Article Stats -->
        <div class="flex items-center space-x-3">
          
          <!-- Views -->
          <div class="flex items-center">
            <EyeIcon class="w-3 h-3 mr-1" />
            <span>{{ formatNumber(article.views_count) }}</span>
          </div>

          <!-- Likes -->
          <div v-if="article.likes_count > 0" class="flex items-center">
            <HeartIcon class="w-3 h-3 mr-1 text-red-500" />
            <span>{{ formatNumber(article.likes_count) }}</span>
          </div>

          <!-- Comments -->
          <div v-if="article.comments_count > 0" class="flex items-center">
            <ChatBubbleLeftIcon class="w-3 h-3 mr-1" />
            <span>{{ formatNumber(article.comments_count) }}</span>
          </div>

        </div>
      </div>

      <!-- Tags -->
      <div v-if="showTags && article.tags && article.tags.length > 0" class="article-tags mt-3 flex flex-wrap gap-1">
        <TagBadge
          v-for="tag in article.tags.slice(0, 3)"
          :key="tag.id"
          :tag="tag"
          size="xs"
          variant="subtle"
        />
        <span v-if="article.tags.length > 3" class="text-xs text-gray-400">
          +{{ article.tags.length - 3 }} lainnya
        </span>
      </div>

    </div>

    <!-- Card Actions (Mobile) -->
    <div v-if="isMobile" class="article-actions flex items-center justify-between p-4 pt-0">
      
      <div class="flex items-center space-x-3">
        <!-- Like Button -->
        <LikeButton
          :is-liked="isLiked"
          :count="article.likes_count"
          :loading="likeLoading"
          size="sm"
          @click.stop="handleLike"
        />
        
        <!-- Comment Count -->
        <button 
          class="flex items-center text-gray-500 hover:text-red-600 transition-colors duration-200"
          @click.stop="scrollToComments"
        >
          <ChatBubbleLeftIcon class="w-4 h-4 mr-1" />
          <span class="text-sm">{{ formatNumber(article.comments_count) }}</span>
        </button>
      </div>

      <!-- Share Button -->
      <ShareButton
        size="sm"
        variant="ghost"
        @click.stop="$emit('share', article)"
      />

    </div>

    <!-- Trending Indicator -->
    <div v-if="article.is_trending" class="trending-indicator absolute -top-2 -right-2">
      <div class="bg-yellow-500 text-black text-xs font-bold px-2 py-1 rounded-full flex items-center animate-pulse">
        <FireIcon class="w-3 h-3 mr-1" />
        TRENDING
      </div>
    </div>

    <!-- Breaking News Indicator -->
    <div v-if="article.is_breaking" class="breaking-indicator absolute -top-2 -left-2">
      <div class="bg-red-600 text-white text-xs font-bold px-2 py-1 rounded-full flex items-center animate-bounce">
        <ExclamationTriangleIcon class="w-3 h-3 mr-1" />
        BREAKING
      </div>
    </div>

  </article>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useBreakpoints } from '@vueuse/core'
import {
  ClockIcon,
  EyeIcon,
  HeartIcon,
  ChatBubbleLeftIcon,
  FireIcon,
  ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'

// Components
import LazyImage from './LazyImage.vue'
import ContentTypeBadge from './ContentTypeBadge.vue'
import CategoryBadge from './CategoryBadge.vue'
import AuthorAvatar from './AuthorAvatar.vue'
import TagBadge from './TagBadge.vue'
import BookmarkButton from './BookmarkButton.vue'
import LikeButton from './LikeButton.vue'
import ShareButton from './ShareButton.vue'

// Composables
import { useBookmarks } from '@/composables/useBookmarks'
import { useLikes } from '@/composables/useLikes'
import { formatRelativeTime, formatNumber } from '@/utils/formatters'
import type { Article } from '@/types'

// Props
interface Props {
  article: Article
  showAuthor?: boolean
  showCategory?: boolean
  showExcerpt?: boolean
  showReadTime?: boolean
  showTags?: boolean
  cardSize?: 'sm' | 'md' | 'lg'
  variant?: 'default' | 'compact' | 'featured'
}

const props = withDefaults(defineProps<Props>(), {
  showAuthor: true,
  showCategory: true,
  showExcerpt: true,
  showReadTime: true,
  showTags: false,
  cardSize: 'md',
  variant: 'default'
})

// Emits
const emit = defineEmits<{
  'click': [article: Article]
  'bookmark': [article: Article]
  'share': [article: Article]
  'author-click': [article: Article]
  'category-click': [article: Article]
}>()

// Composables
const breakpoints = useBreakpoints({
  mobile: 768,
})

const { 
  isBookmarked, 
  toggleBookmark, 
  loading: bookmarkLoading 
} = useBookmarks()

const { 
  isLiked, 
  toggleLike, 
  loading: likeLoading 
} = useLikes()

// Computed
const isMobile = computed(() => breakpoints.smaller('mobile').value)

const cardClasses = computed(() => {
  const base = 'bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 relative overflow-hidden border border-gray-100'
  
  const sizeClasses = {
    sm: 'max-w-sm',
    md: 'max-w-md',
    lg: 'max-w-lg'
  }
  
  const variantClasses = {
    default: 'hover:transform hover:scale-[1.02]',
    compact: 'hover:bg-gray-50',
    featured: 'lg:flex lg:flex-row hover:shadow-lg'
  }
  
  return [
    base,
    sizeClasses[props.cardSize],
    variantClasses[props.variant]
  ].join(' ')
})

const imageClasses = computed(() => {
  const base = 'w-full object-cover transition-transform duration-300 group-hover:scale-105'
  
  const sizeClasses = {
    sm: 'h-40',
    md: 'h-48',
    lg: 'h-56'
  }
  
  const variantClasses = {
    default: '',
    compact: 'h-32',
    featured: 'lg:w-1/2 lg:h-full'
  }
  
  return [
    base,
    sizeClasses[props.cardSize],
    variantClasses[props.variant]
  ].join(' ')
})

// Methods
const handleBookmark = async () => {
  try {
    await toggleBookmark('article', props.article.id)
    emit('bookmark', props.article)
  } catch (error) {
    console.error('Failed to bookmark article:', error)
  }
}

const handleLike = async () => {
  try {
    await toggleLike('article', props.article.id)
  } catch (error) {
    console.error('Failed to like article:', error)
  }
}

const scrollToComments = () => {
  // This would typically navigate to the article page and scroll to comments
  emit('click', props.article)
}
</script>

<style scoped>
.article-card {
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.article-card:hover {
  transform: translateY(-2px);
}

.article-card:focus {
  outline: 2px solid #dc2626;
  outline-offset: 2px;
}

.article-title {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  line-height: 1.4;
  font-size: 1.125rem;
}

.article-excerpt {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  line-height: 1.5;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Image hover effects */
.article-image {
  position: relative;
  overflow: hidden;
}

.article-image::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, transparent 0%, rgba(220, 38, 38, 0.1) 100%);
  opacity: 0;
  transition: opacity 0.3s ease;
}

.article-card:hover .article-image::after {
  opacity: 1;
}

/* Trending and breaking indicators */
.trending-indicator,
.breaking-indicator {
  z-index: 10;
}

/* Featured variant specific styles */
.article-card.featured {
  @media (min-width: 1024px) {
    .article-content {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
  }
}

/* Compact variant specific styles */
.article-card.compact {
  .article-title {
    font-size: 1rem;
    -webkit-line-clamp: 1;
  }
  
  .article-excerpt {
    -webkit-line-clamp: 1;
  }
}

/* Mobile responsive adjustments */
@media (max-width: 767px) {
  .article-title {
    font-size: 1rem;
  }
  
  .article-excerpt {
    font-size: 0.875rem;
  }
  
  .article-meta {
    font-size: 0.75rem;
  }
}

/* High contrast mode */
@media (prefers-contrast: high) {
  .article-card {
    border: 2px solid #000;
  }
  
  .article-card:hover {
    border-color: #dc2626;
  }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .article-card,
  .article-image img,
  .image-overlay {
    transition: none !important;
  }
  
  .article-card:hover {
    transform: none;
  }
  
  .animate-pulse,
  .animate-bounce {
    animation: none !important;
  }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .article-card {
    background-color: #1f2937;
    border-color: #374151;
  }
  
  .article-title {
    color: #f9fafb;
  }
  
  .article-excerpt {
    color: #d1d5db;
  }
  
  .article-meta {
    color: #9ca3af;
  }
}

/* Print styles */
@media print {
  .article-card {
    break-inside: avoid;
    box-shadow: none;
    border: 1px solid #ccc;
  }
  
  .article-actions,
  .trending-indicator,
  .breaking-indicator {
    display: none;
  }
}
</style>