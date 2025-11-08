<template>
  <div class="article-page bg-gray-50 min-h-screen">
    <!-- Loading State -->
    <div v-if="loading" class="article-loading">
      <ArticlePageSkeleton />
    </div>

    <!-- Article Content -->
    <div v-else-if="article" class="article-content">
      
      <!-- Breadcrumb Navigation -->
      <BreadcrumbNav 
        :items="breadcrumbItems"
        class="container mx-auto px-4 py-4"
      />

      <!-- Article Header -->
      <ArticleHeader 
        :article="article"
        :is-bookmarked="isBookmarked"
        :bookmark-loading="bookmarkLoading"
        @bookmark-toggle="handleBookmarkToggle"
        @share="handleShare"
        @report="handleReport"
      />

      <!-- Main Content Grid -->
      <div class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
          
          <!-- Article Body -->
          <div class="lg:col-span-3">
            <article class="article-body bg-white rounded-lg shadow-sm p-6 lg:p-8">
              
              <!-- Article Meta -->
              <ArticleMeta 
                :article="article"
                :show-reading-time="true"
                :show-word-count="true"
                class="mb-6"
              />

              <!-- Featured Image -->
              <div v-if="article.featured_image" class="article-image mb-8">
                <LazyImage
                  :src="article.featured_image"
                  :alt="article.title"
                  class="w-full h-64 lg:h-96 object-cover rounded-lg"
                />
                <p 
                  v-if="article.featured_image_caption" 
                  class="text-sm text-gray-600 mt-2 text-center italic"
                >
                  {{ article.featured_image_caption }}
                </p>
              </div>

              <!-- Article Content -->
              <div class="article-content-body">
                <ArticleContent 
                  :content="article.content"
                  :content-type="article.content_type"
                  @media-click="handleMediaClick"
                  @link-click="handleLinkClick"
                />
              </div>

              <!-- Article Tags -->
              <div v-if="article.tags && article.tags.length > 0" class="article-tags mt-8 pt-6 border-t border-gray-200">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Tags:</h3>
                <div class="flex flex-wrap gap-2">
                  <TagBadge
                    v-for="tag in article.tags"
                    :key="tag.id"
                    :tag="tag"
                    clickable
                    @click="handleTagClick"
                  />
                </div>
              </div>

              <!-- Social Sharing -->
              <SocialSharing 
                :article="article"
                :url="currentUrl"
                class="mt-8 pt-6 border-t border-gray-200"
              />

              <!-- Article Actions -->
              <ArticleActions 
                :article="article"
                :is-liked="isLiked"
                :is-bookmarked="isBookmarked"
                :like-loading="likeLoading"
                :bookmark-loading="bookmarkLoading"
                @like-toggle="handleLikeToggle"
                @bookmark-toggle="handleBookmarkToggle"
                @share="handleShare"
                class="mt-8 pt-6 border-t border-gray-200"
              />

            </article>

            <!-- Author Bio -->
            <AuthorBio 
              v-if="article.author"
              :author="article.author"
              :article-count="authorArticleCount"
              @follow="handleAuthorFollow"
              @view-articles="handleViewAuthorArticles"
              class="mt-8"
            />

            <!-- Related Articles -->
            <RelatedArticles 
              v-if="relatedArticles.length > 0"
              :articles="relatedArticles"
              @article-click="handleRelatedArticleClick"
              class="mt-8"
            />

            <!-- Comments Section -->
            <CommentsSection 
              :article-id="article.id"
              :comments="comments"
              :comments-loading="commentsLoading"
              :can-comment="canComment"
              @comment-submit="handleCommentSubmit"
              @comment-reply="handleCommentReply"
              @comment-like="handleCommentLike"
              @comment-report="handleCommentReport"
              @load-more-comments="loadMoreComments"
              class="mt-8"
            />

          </div>

          <!-- Sidebar -->
          <div class="lg:col-span-1">
            <ArticleSidebar 
              :trending-articles="trendingArticles"
              :popular-tags="popularTags"
              :newsletter-signup="true"
              :ad-zones="sidebarAds"
              @article-click="handleSidebarArticleClick"
              @tag-click="handleTagClick"
              @newsletter-signup="handleNewsletterSignup"
            />
          </div>

        </div>
      </div>

    </div>

    <!-- Error State -->
    <div v-else-if="error" class="article-error">
      <ErrorState 
        :error="error"
        @retry="fetchArticle"
      />
    </div>

    <!-- Article Not Found -->
    <div v-else class="article-not-found">
      <NotFoundState 
        title="Artikel Tidak Ditemukan"
        description="Artikel yang Anda cari tidak ditemukan atau telah dihapus."
        @go-home="$router.push('/')"
      />
    </div>

    <!-- Reading Progress Bar -->
    <ReadingProgressBar 
      :progress="readingProgress"
      class="fixed top-0 left-0 right-0 z-50"
    />

    <!-- Floating Action Buttons (Mobile) -->
    <FloatingActionButtons
      v-if="isMobile && article"
      :is-liked="isLiked"
      :is-bookmarked="isBookmarked"
      :like-loading="likeLoading"
      :bookmark-loading="bookmarkLoading"
      @like-toggle="handleLikeToggle"
      @bookmark-toggle="handleBookmarkToggle"
      @share="handleShare"
      @scroll-to-comments="scrollToComments"
    />

    <!-- Share Modal -->
    <ShareModal 
      v-if="showShareModal"
      :article="article"
      :url="currentUrl"
      @close="showShareModal = false"
    />

    <!-- Report Modal -->
    <ReportModal 
      v-if="showReportModal"
      :article="article"
      @close="showReportModal = false"
      @submit="handleReportSubmit"
    />

  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, onUnmounted, computed, watch, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useBreakpoints } from '@vueuse/core'
import { useHead } from '@vueuse/head'
import { useIntersectionObserver, useScroll } from '@vueuse/core'

// Components
import BreadcrumbNav from '../common/BreadcrumbNav.vue'
import ArticleHeader from './ArticleHeader.vue'
import ArticleMeta from '../common/ArticleMeta.vue'
import LazyImage from '../common/LazyImage.vue'
import ArticleContent from './ArticleContent.vue'
import TagBadge from '../common/TagBadge.vue'
import SocialSharing from '../common/SocialSharing.vue'
import ArticleActions from './ArticleActions.vue'
import AuthorBio from './AuthorBio.vue'
import RelatedArticles from './RelatedArticles.vue'
import CommentsSection from './CommentsSection.vue'
import ArticleSidebar from './ArticleSidebar.vue'
import ReadingProgressBar from '../common/ReadingProgressBar.vue'
import FloatingActionButtons from './FloatingActionButtons.vue'
import ShareModal from '../common/ShareModal.vue'
import ReportModal from '../common/ReportModal.vue'
import ArticlePageSkeleton from '../common/ArticlePageSkeleton.vue'
import ErrorState from '../common/ErrorState.vue'
import NotFoundState from '../common/NotFoundState.vue'

// Stores and Composables
import { useArticleStore } from '@/stores/article'
import { useUserStore } from '@/stores/user'
import { useAnalytics } from '@/composables/useAnalytics'
import { useBookmarks } from '@/composables/useBookmarks'
import { useLikes } from '@/composables/useLikes'
import { useComments } from '@/composables/useComments'
import { useShare } from '@/composables/useShare'

import type { Article, Comment, Tag } from '@/types'

// Composables
const route = useRoute()
const router = useRouter()
const articleStore = useArticleStore()
const userStore = useUserStore()
const { trackEvent, trackPageView } = useAnalytics()
const { isBookmarked, toggleBookmark, loading: bookmarkLoading } = useBookmarks()
const { isLiked, toggleLike, loading: likeLoading } = useLikes()
const { comments, loading: commentsLoading, submitComment, replyToComment, likeComment, reportComment, loadMore: loadMoreComments } = useComments()
const { shareArticle } = useShare()

const breakpoints = useBreakpoints({
  mobile: 768,
  tablet: 1024,
})

// Reactive state
const loading = ref(true)
const error = ref<string | null>(null)
const article = ref<Article | null>(null)
const relatedArticles = ref<Article[]>([])
const trendingArticles = ref<Article[]>([])
const popularTags = ref<Tag[]>([])
const sidebarAds = ref<any[]>([])
const authorArticleCount = ref(0)
const showShareModal = ref(false)
const showReportModal = ref(false)
const readingProgress = ref(0)
let readingProgressInterval: NodeJS.Timeout | null = null

// Computed
const isMobile = computed(() => breakpoints.smaller('tablet').value)
const canComment = computed(() => userStore.isAuthenticated && article.value?.allow_comments)
const currentUrl = computed(() => window.location.href)

const breadcrumbItems = computed(() => {
  if (!article.value) return []
  
  return [
    { label: 'Beranda', href: '/' },
    { label: article.value.category?.name || 'Artikel', href: `/kategori/${article.value.category?.slug}` },
    { label: article.value.title, href: route.path, active: true }
  ]
})

// Methods
const fetchArticle = async () => {
  try {
    loading.value = true
    error.value = null

    const slug = route.params.slug as string
    
    // Fetch article and related data in parallel
    const [
      articleResponse,
      relatedResponse,
      trendingResponse,
      tagsResponse
    ] = await Promise.all([
      articleStore.fetchArticleBySlug(slug),
      articleStore.fetchRelatedArticles(slug, 4),
      articleStore.fetchTrendingArticles(5),
      articleStore.fetchPopularTags(10)
    ])

    article.value = articleResponse.data
    relatedArticles.value = relatedResponse.data
    trendingArticles.value = trendingResponse.data
    popularTags.value = tagsResponse.data

    if (article.value) {
      // Fetch author article count
      authorArticleCount.value = await articleStore.getAuthorArticleCount(article.value.author.id)
      
      // Track page view
      trackPageView('article_view', {
        article_id: article.value.id,
        article_title: article.value.title,
        category_id: article.value.category?.id,
        author_id: article.value.author.id
      })

      // Update SEO meta tags
      updateMetaTags()
    }

  } catch (err: any) {
    console.error('Failed to fetch article:', err)
    error.value = err.message || 'Failed to load article'
  } finally {
    loading.value = false
  }
}

const updateMetaTags = () => {
  if (!article.value) return

  useHead({
    title: article.value.title,
    meta: [
      { name: 'description', content: article.value.excerpt },
      { name: 'keywords', content: article.value.tags?.map(t => t.name).join(', ') },
      { property: 'og:title', content: article.value.title },
      { property: 'og:description', content: article.value.excerpt },
      { property: 'og:image', content: article.value.featured_image },
      { property: 'og:url', content: currentUrl.value },
      { property: 'og:type', content: 'article' },
      { property: 'article:author', content: article.value.author.name },
      { property: 'article:published_time', content: article.value.published_at },
      { property: 'article:section', content: article.value.category?.name },
      { name: 'twitter:card', content: 'summary_large_image' },
      { name: 'twitter:title', content: article.value.title },
      { name: 'twitter:description', content: article.value.excerpt },
      { name: 'twitter:image', content: article.value.featured_image }
    ]
  })
}

const handleBookmarkToggle = async () => {
  if (!article.value) return

  try {
    await toggleBookmark('article', article.value.id)
    
    trackEvent('article_bookmark_toggle', {
      article_id: article.value.id,
      action: isBookmarked.value ? 'bookmark' : 'unbookmark'
    })
  } catch (error) {
    console.error('Failed to toggle bookmark:', error)
  }
}

const handleLikeToggle = async () => {
  if (!article.value) return

  try {
    await toggleLike('article', article.value.id)
    
    trackEvent('article_like_toggle', {
      article_id: article.value.id,
      action: isLiked.value ? 'like' : 'unlike'
    })
  } catch (error) {
    console.error('Failed to toggle like:', error)
  }
}

const handleShare = () => {
  showShareModal.value = true
  
  trackEvent('article_share_intent', {
    article_id: article.value?.id
  })
}

const handleReport = () => {
  showReportModal.value = true
}

const handleReportSubmit = async (reportData: any) => {
  try {
    await articleStore.reportArticle(article.value!.id, reportData)
    showReportModal.value = false
    
    trackEvent('article_reported', {
      article_id: article.value?.id,
      reason: reportData.reason
    })
    
    // Show success notification
  } catch (error) {
    console.error('Failed to report article:', error)
  }
}

const handleTagClick = (tag: Tag) => {
  router.push(`/tag/${tag.slug}`)
  
  trackEvent('tag_click', {
    tag_id: tag.id,
    tag_name: tag.name,
    source: 'article_page'
  })
}

const handleAuthorFollow = async (authorId: number) => {
  try {
    await userStore.followUser(authorId)
    
    trackEvent('author_follow', {
      author_id: authorId,
      article_id: article.value?.id
    })
  } catch (error) {
    console.error('Failed to follow author:', error)
  }
}

const handleViewAuthorArticles = (authorId: number) => {
  router.push(`/penulis/${authorId}`)
  
  trackEvent('view_author_articles', {
    author_id: authorId,
    article_id: article.value?.id
  })
}

const handleRelatedArticleClick = (relatedArticle: Article) => {
  router.push(`/artikel/${relatedArticle.slug}`)
  
  trackEvent('related_article_click', {
    article_id: relatedArticle.id,
    source_article_id: article.value?.id
  })
}

const handleSidebarArticleClick = (sidebarArticle: Article) => {
  router.push(`/artikel/${sidebarArticle.slug}`)
  
  trackEvent('sidebar_article_click', {
    article_id: sidebarArticle.id,
    source_article_id: article.value?.id
  })
}

const handleCommentSubmit = async (commentData: any) => {
  if (!article.value) return

  try {
    await submitComment(article.value.id, commentData)
    
    trackEvent('comment_submit', {
      article_id: article.value.id
    })
  } catch (error) {
    console.error('Failed to submit comment:', error)
  }
}

const handleCommentReply = async (commentId: number, replyData: any) => {
  try {
    await replyToComment(commentId, replyData)
    
    trackEvent('comment_reply', {
      comment_id: commentId,
      article_id: article.value?.id
    })
  } catch (error) {
    console.error('Failed to reply to comment:', error)
  }
}

const handleCommentLike = async (commentId: number) => {
  try {
    await likeComment(commentId)
    
    trackEvent('comment_like', {
      comment_id: commentId,
      article_id: article.value?.id
    })
  } catch (error) {
    console.error('Failed to like comment:', error)
  }
}

const handleCommentReport = async (commentId: number, reportData: any) => {
  try {
    await reportComment(commentId, reportData)
    
    trackEvent('comment_report', {
      comment_id: commentId,
      article_id: article.value?.id,
      reason: reportData.reason
    })
  } catch (error) {
    console.error('Failed to report comment:', error)
  }
}

const handleNewsletterSignup = async (email: string) => {
  try {
    await userStore.subscribeNewsletter(email)
    
    trackEvent('newsletter_signup', {
      source: 'article_sidebar',
      article_id: article.value?.id
    })
  } catch (error) {
    console.error('Failed to signup for newsletter:', error)
  }
}

const handleMediaClick = (mediaUrl: string) => {
  trackEvent('article_media_click', {
    media_url: mediaUrl,
    article_id: article.value?.id
  })
}

const handleLinkClick = (linkUrl: string) => {
  trackEvent('article_link_click', {
    link_url: linkUrl,
    article_id: article.value?.id
  })
}

const scrollToComments = () => {
  const commentsElement = document.getElementById('comments-section')
  if (commentsElement) {
    commentsElement.scrollIntoView({ behavior: 'smooth' })
  }
}

// Reading progress tracking
const { y: scrollY } = useScroll(window)
watch(scrollY, (newY) => {
  const windowHeight = window.innerHeight
  const documentHeight = document.documentElement.scrollHeight
  const progress = (newY / (documentHeight - windowHeight)) * 100
  readingProgress.value = Math.min(Math.max(progress, 0), 100)
})

// Lifecycle
onMounted(() => {
  fetchArticle()
  
  // Start reading progress tracking
  readingProgressInterval = setInterval(() => {
    const scrollTop = window.pageYOffset || document.documentElement.scrollTop
    const scrollHeight = document.documentElement.scrollHeight - window.innerHeight
    
    if (scrollHeight > 0) {
      readingProgress.value = Math.min(100, Math.round((scrollTop / scrollHeight) * 100))
    }
  }, 100)
})

// Cleanup function for preventing memory leaks
onUnmounted(() => {
  // Clear any intervals, timeouts, or event listeners
  if (readingProgressInterval) {
    clearInterval(readingProgressInterval)
  }
  
  // Reset reactive state to prevent memory leaks
  article.value = null
  relatedArticles.value = []
  trendingArticles.value = []
  popularTags.value = []
  comments.value = []
})

// Watch for route changes  
watch(() => route.params.slug, () => {
  if (route.name === 'article') {
    fetchArticle()
  }
})
</script>

<style scoped>
.article-page {
  font-family: 'Inter', sans-serif;
}

.article-body {
  line-height: 1.7;
}

.article-content-body {
  font-size: 1.125rem;
  color: #374151;
}

.article-content-body :deep(p) {
  margin-bottom: 1.5rem;
}

.article-content-body :deep(h1),
.article-content-body :deep(h2),
.article-content-body :deep(h3),
.article-content-body :deep(h4),
.article-content-body :deep(h5),
.article-content-body :deep(h6) {
  margin-top: 2rem;
  margin-bottom: 1rem;
  font-weight: 600;
  color: #111827;
}

.article-content-body :deep(h1) { font-size: 2rem; }
.article-content-body :deep(h2) { font-size: 1.75rem; }
.article-content-body :deep(h3) { font-size: 1.5rem; }
.article-content-body :deep(h4) { font-size: 1.25rem; }

.article-content-body :deep(ul),
.article-content-body :deep(ol) {
  margin-bottom: 1.5rem;
  padding-left: 1.5rem;
}

.article-content-body :deep(li) {
  margin-bottom: 0.5rem;
}

.article-content-body :deep(blockquote) {
  border-left: 4px solid #dc2626;
  padding-left: 1rem;
  margin: 1.5rem 0;
  font-style: italic;
  color: #6b7280;
}

.article-content-body :deep(img) {
  max-width: 100%;
  height: auto;
  border-radius: 0.5rem;
  margin: 1.5rem 0;
}

.article-content-body :deep(a) {
  color: #dc2626;
  text-decoration: underline;
  transition: color 0.2s ease;
}

.article-content-body :deep(a:hover) {
  color: #991b1b;
}

/* Responsive adjustments */
@media (max-width: 1023px) {
  .article-content-body {
    font-size: 1rem;
  }
}

@media (max-width: 767px) {
  .article-body {
    padding: 1rem;
  }
  
  .article-content-body {
    font-size: 0.9375rem;
  }
}

/* Print styles */
@media print {
  .article-page {
    background: white;
  }
  
  .article-body {
    box-shadow: none;
    border: none;
  }
}
</style>