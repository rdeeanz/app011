<template>
  <div class="articles-grid">
    <!-- Loading State -->
    <div v-if="loading" class="grid-loading">
      <div 
        v-for="n in skeletonCount" 
        :key="n"
        class="animate-pulse"
      >
        <ArticleCardSkeleton />
      </div>
    </div>

    <!-- Articles Grid -->
    <div v-else-if="articles.length > 0" class="articles-container">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <ArticleCard
          v-for="article in articles"
          :key="article.id"
          :article="article"
          :show-author="showAuthor"
          :show-category="showCategory"
          :show-excerpt="showExcerpt"
          :show-read-time="showReadTime"
          :card-size="cardSize"
          @click="$emit('article-click', article)"
          @bookmark="handleBookmark"
          @share="handleShare"
          @author-click="handleAuthorClick"
          @category-click="handleCategoryClick"
        />
      </div>

      <!-- Load More Button -->
      <div v-if="hasMore && !loading" class="load-more-container mt-8 text-center">
        <button
          @click="$emit('load-more')"
          :disabled="loadingMore"
          class="load-more-btn bg-red-600 hover:bg-red-700 disabled:bg-gray-400 text-white font-semibold px-8 py-3 rounded-lg transition-colors duration-200 inline-flex items-center"
        >
          <span v-if="!loadingMore">Muat Lebih Banyak</span>
          <span v-else class="flex items-center">
            <LoadingSpinner class="w-5 h-5 mr-2" />
            Memuat...
          </span>
        </button>
      </div>

      <!-- Loading More Indicator -->
      <div v-if="loadingMore" class="loading-more mt-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <ArticleCardSkeleton
            v-for="n in 3"
            :key="n"
          />
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="empty-state">
      <EmptyState 
        :title="emptyTitle"
        :description="emptyDescription"
        :action-text="emptyActionText"
        @action="$emit('empty-action')"
      />
    </div>

    <!-- Error State -->
    <div v-if="error" class="error-state mt-6">
      <ErrorBanner 
        :message="error"
        @retry="$emit('retry')"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import ArticleCard from './ArticleCard.vue'
import ArticleCardSkeleton from './ArticleCardSkeleton.vue'
import EmptyState from './EmptyState.vue'
import ErrorBanner from './ErrorBanner.vue'
import LoadingSpinner from './LoadingSpinner.vue'
import { useAnalytics } from '@/composables/useAnalytics'
import { useBookmarks } from '@/composables/useBookmarks'
import { useShare } from '@/composables/useShare'
import type { Article } from '@/types'

// Props
interface Props {
  articles: Article[]
  loading?: boolean
  loadingMore?: boolean
  hasMore?: boolean
  error?: string | null
  showAuthor?: boolean
  showCategory?: boolean
  showExcerpt?: boolean
  showReadTime?: boolean
  cardSize?: 'sm' | 'md' | 'lg'
  emptyTitle?: string
  emptyDescription?: string
  emptyActionText?: string
  skeletonCount?: number
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  loadingMore: false,
  hasMore: false,
  error: null,
  showAuthor: true,
  showCategory: true,
  showExcerpt: true,
  showReadTime: true,
  cardSize: 'md',
  emptyTitle: 'Tidak Ada Artikel',
  emptyDescription: 'Belum ada artikel yang tersedia saat ini.',
  emptyActionText: 'Kembali ke Beranda',
  skeletonCount: 6
})

// Emits
const emit = defineEmits<{
  'article-click': [article: Article]
  'load-more': []
  'empty-action': []
  'retry': []
}>()

// Composables
const { trackEvent } = useAnalytics()
const { toggleBookmark } = useBookmarks()
const { shareArticle } = useShare()

// Methods
const handleBookmark = async (article: Article) => {
  try {
    await toggleBookmark('article', article.id)
    
    trackEvent('article_bookmark', {
      article_id: article.id,
      article_title: article.title,
      source: 'articles_grid'
    })
  } catch (error) {
    console.error('Failed to bookmark article:', error)
  }
}

const handleShare = (article: Article) => {
  shareArticle(article)
  
  trackEvent('article_share', {
    article_id: article.id,
    article_title: article.title,
    source: 'articles_grid'
  })
}

const handleAuthorClick = (article: Article) => {
  trackEvent('author_click', {
    author_id: article.author.id,
    author_name: article.author.name,
    article_id: article.id,
    source: 'articles_grid'
  })
}

const handleCategoryClick = (article: Article) => {
  trackEvent('category_click', {
    category_id: article.category?.id,
    category_name: article.category?.name,
    article_id: article.id,
    source: 'articles_grid'
  })
}
</script>

<style scoped>
.articles-grid {
  width: 100%;
}

.articles-container {
  animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.load-more-btn {
  transition: all 0.2s ease;
  box-shadow: 0 2px 8px rgba(220, 38, 38, 0.2);
}

.load-more-btn:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
}

.load-more-btn:disabled {
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

/* Grid responsive adjustments */
@media (max-width: 767px) {
  .articles-container .grid {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
}

@media (min-width: 768px) and (max-width: 1023px) {
  .articles-container .grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
  }
}

@media (min-width: 1024px) {
  .articles-container .grid {
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
  }
}

/* Loading states animation */
.grid-loading {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
}

.loading-more {
  animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Empty and error states */
.empty-state,
.error-state {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 300px;
}

/* Accessibility improvements */
.load-more-btn:focus {
  outline: 2px solid #dc2626;
  outline-offset: 2px;
}

/* High contrast mode */
@media (prefers-contrast: high) {
  .load-more-btn {
    border: 2px solid currentColor;
  }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .articles-container,
  .loading-more,
  .load-more-btn {
    animation: none !important;
    transition: none !important;
  }
  
  .load-more-btn:hover:not(:disabled) {
    transform: none;
  }
}

/* Print styles */
@media print {
  .load-more-container,
  .loading-more,
  .error-state {
    display: none;
  }
  
  .articles-container .grid {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
}
</style>