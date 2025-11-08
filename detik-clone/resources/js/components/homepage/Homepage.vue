<template>
  <div class="homepage">
    <!-- Hero Section -->
    <HeroSection 
      :featured-articles="featuredArticles"
      :loading="loading"
      @article-click="handleArticleClick"
    />

    <!-- Breaking News Ticker -->
    <BreakingNewsTicker 
      v-if="breakingNews.length > 0"
      :news="breakingNews"
      @news-click="handleArticleClick"
    />

    <!-- Main Content Grid -->
    <div class="container mx-auto px-4 py-8">
      <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        
        <!-- Main Content Area -->
        <div class="lg:col-span-3">
          <!-- Category Tabs -->
          <CategoryTabs 
            :categories="categories"
            :active-category="activeCategory"
            @category-change="handleCategoryChange"
          />

          <!-- Articles Grid -->
          <ArticlesGrid 
            :articles="articles"
            :loading="articlesLoading"
            :has-more="hasMoreArticles"
            @load-more="loadMoreArticles"
            @article-click="handleArticleClick"
          />
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1">
          <Sidebar 
            :trending-articles="trendingArticles"
            :popular-tags="popularTags"
            :newsletter-signup="true"
            @tag-click="handleTagClick"
            @article-click="handleArticleClick"
          />
        </div>

      </div>
    </div>

    <!-- Load More Articles (Mobile Infinite Scroll) -->
    <InfiniteScroll 
      v-if="isMobile"
      @reached="loadMoreArticles"
      :loading="articlesLoading"
      :has-more="hasMoreArticles"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, onUnmounted, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useBreakpoints } from '@vueuse/core'
import HeroSection from './HeroSection.vue'
import BreakingNewsTicker from './BreakingNewsTicker.vue'
import CategoryTabs from './CategoryTabs.vue'
import ArticlesGrid from '../common/ArticlesGrid.vue'
import Sidebar from '../common/Sidebar.vue'
import InfiniteScroll from '../common/InfiniteScroll.vue'
import { useHomepageStore } from '@/stores/homepage'
import { useAnalytics } from '@/composables/useAnalytics'
import type { Article, Category, Tag } from '@/types'

// Composables
const router = useRouter()
const homepageStore = useHomepageStore()
const { trackEvent } = useAnalytics()
const breakpoints = useBreakpoints({
  mobile: 0,
  tablet: 768,
  desktop: 1024,
})

// Reactive state
const loading = ref(true)
const articlesLoading = ref(false)
const activeCategory = ref<number | null>(null)
const currentPage = ref(1)

// Data
const featuredArticles = ref<Article[]>([])
const breakingNews = ref<Article[]>([])
const articles = ref<Article[]>([])
const categories = ref<Category[]>([])
const trendingArticles = ref<Article[]>([])
const popularTags = ref<Tag[]>([])

// Computed
const isMobile = computed(() => breakpoints.smaller('tablet').value)
const hasMoreArticles = computed(() => homepageStore.hasMoreArticles)

// Methods
const fetchInitialData = async () => {
  try {
    loading.value = true
    
    // Fetch all initial data in parallel
    const [
      featuredRes,
      breakingRes,
      articlesRes,
      categoriesRes,
      trendingRes,
      tagsRes
    ] = await Promise.all([
      homepageStore.fetchFeaturedArticles(),
      homepageStore.fetchBreakingNews(),
      homepageStore.fetchArticles({ page: 1, category: activeCategory.value }),
      homepageStore.fetchCategories(),
      homepageStore.fetchTrendingArticles(),
      homepageStore.fetchPopularTags()
    ])

    featuredArticles.value = featuredRes.data
    breakingNews.value = breakingRes.data
    articles.value = articlesRes.data
    categories.value = categoriesRes.data
    trendingArticles.value = trendingRes.data
    popularTags.value = tagsRes.data

    // Track page view
    trackEvent('homepage_view', {
      featured_count: featuredArticles.value.length,
      breaking_count: breakingNews.value.length,
      articles_count: articles.value.length
    })

  } catch (error) {
    console.error('Failed to fetch homepage data:', error)
    // Show error toast or notification
  } finally {
    loading.value = false
  }
}

const handleCategoryChange = async (categoryId: number | null) => {
  if (activeCategory.value === categoryId) return

  activeCategory.value = categoryId
  currentPage.value = 1
  articlesLoading.value = true

  try {
    const response = await homepageStore.fetchArticles({
      page: 1,
      category: categoryId
    })
    
    articles.value = response.data

    // Track category change
    trackEvent('category_change', {
      category_id: categoryId,
      category_name: categories.value.find(c => c.id === categoryId)?.name || 'all'
    })

  } catch (error) {
    console.error('Failed to fetch category articles:', error)
  } finally {
    articlesLoading.value = false
  }
}

const loadMoreArticles = async () => {
  if (articlesLoading.value || !hasMoreArticles.value) return

  articlesLoading.value = true
  currentPage.value++

  try {
    const response = await homepageStore.fetchArticles({
      page: currentPage.value,
      category: activeCategory.value
    })

    articles.value.push(...response.data)

    // Track load more
    trackEvent('load_more_articles', {
      page: currentPage.value,
      category_id: activeCategory.value
    })

  } catch (error) {
    console.error('Failed to load more articles:', error)
    currentPage.value-- // Revert page increment
  } finally {
    articlesLoading.value = false
  }
}

const handleArticleClick = (article: Article) => {
  // Track article click
  trackEvent('article_click', {
    article_id: article.id,
    article_title: article.title,
    category_id: article.category_id,
    position: 'homepage'
  })

  // Navigate to article
  router.push(`/artikel/${article.slug}`)
}

const handleTagClick = (tag: Tag) => {
  // Track tag click
  trackEvent('tag_click', {
    tag_id: tag.id,
    tag_name: tag.name,
    position: 'sidebar'
  })

  // Navigate to tag page
  router.push(`/tag/${tag.slug}`)
}

// Watchers
watch(activeCategory, (newCategory) => {
  // Update URL without navigation
  const query = newCategory ? { category: newCategory } : {}
  router.replace({ query })
})

// Lifecycle
onMounted(() => {
  // Get category from URL query
  const categoryQuery = router.currentRoute.value.query.category
  if (categoryQuery) {
    activeCategory.value = parseInt(categoryQuery as string) || null
  }

  fetchInitialData()
})

// Auto-refresh breaking news every 5 minutes
const refreshBreakingNews = async () => {
  try {
    const response = await homepageStore.fetchBreakingNews()
    breakingNews.value = response.data
  } catch (error) {
    console.error('Failed to refresh breaking news:', error)
  }
}

// Set up auto-refresh interval
let refreshInterval: ReturnType<typeof setInterval> | null = null

onMounted(() => {
  refreshInterval = setInterval(refreshBreakingNews, 5 * 60 * 1000) // 5 minutes
})

onUnmounted(() => {
  if (refreshInterval) {
    clearInterval(refreshInterval)
  }
})
</script>

<style scoped>
.homepage {
  min-height: 100vh;
  background-color: #f8fafc;
}

.container {
  max-width: 1280px;
}

/* Responsive grid adjustments */
@media (max-width: 1023px) {
  .grid {
    grid-template-columns: 1fr;
  }
}

/* Loading states */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Smooth scrolling for infinite scroll */
.articles-container {
  scroll-behavior: smooth;
}
</style>