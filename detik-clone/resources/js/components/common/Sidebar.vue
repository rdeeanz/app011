<template>
  <aside class="sidebar space-y-6">
    
    <!-- Trending Articles -->
    <div v-if="trendingArticles.length > 0" class="trending-section">
      <SidebarSection title="Trending" icon="fire">
        <div class="space-y-4">
          <TrendingArticleCard
            v-for="(article, index) in trendingArticles"
            :key="article.id"
            :article="article"
            :rank="index + 1"
            @click="$emit('article-click', article)"
          />
        </div>
      </SidebarSection>
    </div>

    <!-- Popular Tags -->
    <div v-if="popularTags.length > 0" class="tags-section">
      <SidebarSection title="Tag Populer" icon="hashtag">
        <div class="tag-cloud">
          <TagBadge
            v-for="tag in popularTags"
            :key="tag.id"
            :tag="tag"
            :size="getTagSize(tag.trending_score)"
            clickable
            @click="$emit('tag-click', tag)"
            class="m-1"
          />
        </div>
      </SidebarSection>
    </div>

    <!-- Newsletter Signup -->
    <div v-if="newsletterSignup" class="newsletter-section">
      <SidebarSection title="Newsletter" icon="mail">
        <div class="bg-gradient-to-br from-red-50 to-orange-50 p-4 rounded-lg">
          <h3 class="font-semibold text-gray-900 mb-2">Dapatkan Berita Terbaru</h3>
          <p class="text-sm text-gray-600 mb-4">
            Berlangganan newsletter kami untuk mendapatkan update berita terkini langsung ke email Anda.
          </p>
          
          <form @submit.prevent="handleNewsletterSubmit" class="space-y-3">
            <div>
              <input
                v-model="newsletterEmail"
                type="email"
                placeholder="Email Anda"
                required
                :disabled="newsletterLoading"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed"
              />
            </div>
            
            <button
              type="submit"
              :disabled="newsletterLoading || !newsletterEmail"
              class="w-full bg-red-600 hover:bg-red-700 disabled:bg-gray-400 text-white font-medium py-2 px-4 rounded-md transition-colors duration-200 flex items-center justify-center"
            >
              <span v-if="!newsletterLoading">Berlangganan</span>
              <span v-else class="flex items-center">
                <LoadingSpinner class="w-4 h-4 mr-2" />
                Memproses...
              </span>
            </button>
          </form>

          <p class="text-xs text-gray-500 mt-2">
            Dengan berlangganan, Anda menyetujui <a href="/privacy" class="text-red-600 hover:underline">kebijakan privasi</a> kami.
          </p>
        </div>
      </SidebarSection>
    </div>

    <!-- Latest Comments -->
    <div v-if="latestComments && latestComments.length > 0" class="comments-section">
      <SidebarSection title="Komentar Terbaru" icon="chat">
        <div class="space-y-3">
          <CommentPreview
            v-for="comment in latestComments"
            :key="comment.id"
            :comment="comment"
            @click="handleCommentClick"
          />
        </div>
      </SidebarSection>
    </div>

    <!-- Weather Widget -->
    <div v-if="showWeather" class="weather-section">
      <SidebarSection title="Cuaca" icon="sun">
        <WeatherWidget 
          :location="weatherLocation"
          @location-change="handleWeatherLocationChange"
        />
      </SidebarSection>
    </div>

    <!-- Ad Zones -->
    <div v-for="adZone in adZones" :key="adZone.id" class="ad-section">
      <AdBanner 
        :zone="adZone"
        :position="adZone.position"
        size="sidebar"
      />
    </div>

    <!-- Social Media Links -->
    <div v-if="socialLinks" class="social-section">
      <SidebarSection title="Ikuti Kami" icon="share">
        <div class="flex flex-wrap gap-2">
          <SocialButton
            v-for="social in socialLinks"
            :key="social.platform"
            :platform="social.platform"
            :url="social.url"
            :followers="social.followers"
            size="sm"
          />
        </div>
      </SidebarSection>
    </div>

    <!-- Quick Links -->
    <div v-if="quickLinks && quickLinks.length > 0" class="links-section">
      <SidebarSection title="Tautan Cepat" icon="link">
        <nav class="space-y-2">
          <a
            v-for="link in quickLinks"
            :key="link.id"
            :href="link.url"
            class="block text-sm text-gray-600 hover:text-red-600 hover:underline transition-colors duration-200"
            :target="link.external ? '_blank' : '_self'"
            :rel="link.external ? 'noopener noreferrer' : ''"
          >
            {{ link.title }}
            <ExternalLinkIcon v-if="link.external" class="inline w-3 h-3 ml-1" />
          </a>
        </nav>
      </SidebarSection>
    </div>

    <!-- Archive Links -->
    <div class="archive-section">
      <SidebarSection title="Arsip" icon="calendar">
        <div class="space-y-2">
          <ArchiveLink
            v-for="archive in archiveLinks"
            :key="archive.period"
            :period="archive.period"
            :count="archive.count"
            @click="handleArchiveClick"
          />
        </div>
      </SidebarSection>
    </div>

    <!-- Back to Top Button -->
    <div class="back-to-top">
      <button
        v-show="showBackToTop"
        @click="scrollToTop"
        class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-4 rounded-md transition-colors duration-200 flex items-center justify-center"
      >
        <ChevronUpIcon class="w-4 h-4 mr-2" />
        Kembali ke Atas
      </button>
    </div>

  </aside>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useScroll } from '@vueuse/core'
import { ExternalLinkIcon, ChevronUpIcon } from '@heroicons/vue/24/outline'

// Components
import SidebarSection from './SidebarSection.vue'
import TrendingArticleCard from './TrendingArticleCard.vue'
import TagBadge from './TagBadge.vue'
import CommentPreview from './CommentPreview.vue'
import WeatherWidget from './WeatherWidget.vue'
import AdBanner from './AdBanner.vue'
import SocialButton from './SocialButton.vue'
import ArchiveLink from './ArchiveLink.vue'
import LoadingSpinner from './LoadingSpinner.vue'

// Composables
import { useAnalytics } from '@/composables/useAnalytics'
import type { Article, Tag, Comment } from '@/types'

// Props
interface Props {
  trendingArticles?: Article[]
  popularTags?: Tag[]
  latestComments?: Comment[]
  newsletterSignup?: boolean
  showWeather?: boolean
  weatherLocation?: string
  adZones?: any[]
  socialLinks?: any[]
  quickLinks?: any[]
  archiveLinks?: any[]
}

const props = withDefaults(defineProps<Props>(), {
  trendingArticles: () => [],
  popularTags: () => [],
  latestComments: () => [],
  newsletterSignup: false,
  showWeather: false,
  weatherLocation: 'Jakarta',
  adZones: () => [],
  socialLinks: () => [],
  quickLinks: () => [],
  archiveLinks: () => []
})

// Emits
const emit = defineEmits<{
  'article-click': [article: Article]
  'tag-click': [tag: Tag]
  'newsletter-signup': [email: string]
}>()

// Reactive state
const newsletterEmail = ref('')
const newsletterLoading = ref(false)

// Scroll tracking
const { y: scrollY } = useScroll(window)
const showBackToTop = computed(() => scrollY.value > 500)

// Composables
const { trackEvent } = useAnalytics()

// Methods
const getTagSize = (trendingScore: number): 'xs' | 'sm' | 'md' | 'lg' => {
  if (trendingScore >= 80) return 'lg'
  if (trendingScore >= 60) return 'md'
  if (trendingScore >= 40) return 'sm'
  return 'xs'
}

const handleNewsletterSubmit = async () => {
  if (!newsletterEmail.value || newsletterLoading.value) return

  try {
    newsletterLoading.value = true
    
    emit('newsletter-signup', newsletterEmail.value)
    
    // Track newsletter signup
    trackEvent('newsletter_signup', {
      email: newsletterEmail.value,
      source: 'sidebar'
    })
    
    // Reset form
    newsletterEmail.value = ''
    
    // Show success message (you might want to use a notification system)
    
  } catch (error) {
    console.error('Newsletter signup failed:', error)
  } finally {
    newsletterLoading.value = false
  }
}

const handleCommentClick = (comment: Comment) => {
  // Navigate to article with comment highlighted
  const articleUrl = `/artikel/${comment.article.slug}#comment-${comment.id}`
  window.location.href = articleUrl
  
  trackEvent('sidebar_comment_click', {
    comment_id: comment.id,
    article_id: comment.article.id
  })
}

const handleWeatherLocationChange = (location: string) => {
  trackEvent('weather_location_change', {
    location: location,
    source: 'sidebar'
  })
}

const handleArchiveClick = (period: string) => {
  // Navigate to archive page
  window.location.href = `/arsip/${period}`
  
  trackEvent('archive_click', {
    period: period,
    source: 'sidebar'
  })
}

const scrollToTop = () => {
  window.scrollTo({
    top: 0,
    behavior: 'smooth'
  })
  
  trackEvent('scroll_to_top', {
    source: 'sidebar'
  })
}

// Generate archive links based on current date
onMounted(() => {
  // You might want to fetch these from an API
  const generateArchiveLinks = () => {
    const now = new Date()
    const archives = []
    
    for (let i = 0; i < 6; i++) {
      const date = new Date(now.getFullYear(), now.getMonth() - i, 1)
      const period = date.toISOString().slice(0, 7) // YYYY-MM format
      const monthName = date.toLocaleDateString('id-ID', { year: 'numeric', month: 'long' })
      
      archives.push({
        period: period,
        label: monthName,
        count: Math.floor(Math.random() * 50) + 10 // Mock count
      })
    }
    
    return archives
  }
  
  if (!props.archiveLinks || props.archiveLinks.length === 0) {
    // You might want to emit an event to parent to fetch real archive data
  }
})
</script>

<style scoped>
.sidebar {
  position: sticky;
  top: 1rem;
  max-height: calc(100vh - 2rem);
  overflow-y: auto;
}

/* Custom scrollbar for sidebar */
.sidebar::-webkit-scrollbar {
  width: 4px;
}

.sidebar::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 2px;
}

.sidebar::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 2px;
}

.sidebar::-webkit-scrollbar-thumb:hover {
  background: #a1a1a1;
}

/* Tag cloud styling */
.tag-cloud {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  align-items: center;
}

/* Newsletter form styling */
.newsletter-section input:focus {
  border-color: #dc2626;
  box-shadow: 0 0 0 2px rgba(220, 38, 38, 0.1);
}

/* Social buttons grid */
.social-section .flex {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
  gap: 0.5rem;
}

/* Responsive adjustments */
@media (max-width: 1023px) {
  .sidebar {
    position: static;
    max-height: none;
    overflow-y: visible;
  }
}

@media (max-width: 767px) {
  .sidebar {
    padding: 0;
  }
  
  .sidebar > div {
    margin-bottom: 1.5rem;
  }
}

/* High contrast mode */
@media (prefers-contrast: high) {
  .sidebar {
    border: 1px solid #000;
  }
  
  .newsletter-section input {
    border: 2px solid #000;
  }
  
  .newsletter-section button {
    border: 2px solid currentColor;
  }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .sidebar {
    scroll-behavior: auto;
  }
  
  .sidebar * {
    transition: none !important;
  }
}

/* Print styles */
@media print {
  .sidebar {
    display: none;
  }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .sidebar {
    background-color: #1f2937;
  }
  
  .newsletter-section input {
    background-color: #374151;
    border-color: #4b5563;
    color: #f9fafb;
  }
  
  .newsletter-section input::placeholder {
    color: #9ca3af;
  }
}
</style>