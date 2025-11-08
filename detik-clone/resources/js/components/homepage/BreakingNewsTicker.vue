<template>
  <div class="breaking-news-ticker bg-yellow-500 text-black shadow-lg">
    <div class="container mx-auto px-4">
      <div class="flex items-center py-3">
        
        <!-- Breaking News Label -->
        <div class="breaking-label flex items-center mr-6 flex-shrink-0">
          <div class="flex items-center bg-red-600 text-white px-4 py-2 rounded-lg font-bold">
            <span class="animate-pulse w-2 h-2 bg-yellow-400 rounded-full mr-2"></span>
            BREAKING NEWS
          </div>
        </div>

        <!-- News Ticker Content -->
        <div class="ticker-container flex-1 overflow-hidden relative">
          <div 
            ref="tickerContent"
            class="ticker-content flex items-center whitespace-nowrap"
            :class="{ 'animate-scroll': isAnimating }"
            @mouseenter="pauseAnimation"
            @mouseleave="resumeAnimation"
          >
            <div 
              v-for="(newsItem, index) in news" 
              :key="newsItem.id"
              class="ticker-item flex items-center mr-8 cursor-pointer hover:text-red-700 transition-colors duration-200"
              @click="handleNewsClick(newsItem)"
            >
              <!-- News Icon -->
              <ExclamationTriangleIcon class="w-4 h-4 mr-2 flex-shrink-0 text-red-600" />
              
              <!-- News Content -->
              <span class="font-semibold text-sm lg:text-base">
                {{ newsItem.title }}
              </span>
              
              <!-- Timestamp -->
              <span class="ml-3 text-xs text-black opacity-60 hidden sm:inline">
                {{ formatRelativeTime(newsItem.published_at) }}
              </span>

              <!-- Separator -->
              <span v-if="index < news.length - 1" class="mx-4 text-red-600">•</span>
            </div>

            <!-- Duplicate content for seamless loop -->
            <div 
              v-for="(newsItem, index) in news" 
              :key="`dup-${newsItem.id}`"
              class="ticker-item flex items-center mr-8 cursor-pointer hover:text-red-700 transition-colors duration-200"
              @click="handleNewsClick(newsItem)"
            >
              <ExclamationTriangleIcon class="w-4 h-4 mr-2 flex-shrink-0 text-red-600" />
              <span class="font-semibold text-sm lg:text-base">
                {{ newsItem.title }}
              </span>
              <span class="ml-3 text-xs text-black opacity-60 hidden sm:inline">
                {{ formatRelativeTime(newsItem.published_at) }}
              </span>
              <span v-if="index < news.length - 1" class="mx-4 text-red-600">•</span>
            </div>
          </div>
        </div>

        <!-- Controls -->
        <div class="ticker-controls flex items-center ml-4 space-x-2 flex-shrink-0">
          
          <!-- Play/Pause Button -->
          <button
            @click="toggleAnimation"
            class="control-btn p-2 rounded-full hover:bg-yellow-400 transition-colors duration-200"
            :title="isAnimating ? 'Pause' : 'Play'"
          >
            <PauseIcon v-if="isAnimating" class="w-4 h-4" />
            <PlayIcon v-else class="w-4 h-4" />
          </button>

          <!-- Close Button -->
          <button
            @click="$emit('close')"
            class="control-btn p-2 rounded-full hover:bg-yellow-400 transition-colors duration-200"
            title="Close Breaking News"
          >
            <XMarkIcon class="w-4 h-4" />
          </button>

        </div>

      </div>
    </div>

    <!-- Progress Bar -->
    <div class="progress-bar h-1 bg-yellow-400">
      <div 
        class="progress-fill h-full bg-red-600 transition-all duration-1000 ease-linear"
        :style="{ width: `${progressPercentage}%` }"
      ></div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed, nextTick, watch } from 'vue'
import { 
  ExclamationTriangleIcon,
  PlayIcon,
  PauseIcon,
  XMarkIcon
} from '@heroicons/vue/24/outline'
import { formatRelativeTime } from '@/utils/formatters'
import type { Article } from '@/types'

// Props
interface Props {
  news: Article[]
  autoPlay?: boolean
  speed?: number
  showProgress?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  autoPlay: true,
  speed: 60, // seconds for full scroll
  showProgress: true
})

// Emits
const emit = defineEmits<{
  'news-click': [article: Article]
  'close': []
}>()

// Reactive state
const tickerContent = ref<HTMLElement | null>(null)
const isAnimating = ref(props.autoPlay)
const isPaused = ref(false)
const animationId = ref<number | null>(null)
const progressPercentage = ref(0)
const startTime = ref<number>(Date.now())

// Computed
const animationDuration = computed(() => `${props.speed}s`)

// Methods
const startAnimation = () => {
  if (!tickerContent.value || props.news.length === 0) return

  isAnimating.value = true
  isPaused.value = false
  startTime.value = Date.now()

  const animate = () => {
    if (!isAnimating.value || isPaused.value) return

    const elapsed = Date.now() - startTime.value
    const duration = props.speed * 1000
    const progress = (elapsed % duration) / duration
    
    progressPercentage.value = progress * 100

    animationId.value = requestAnimationFrame(animate)
  }

  animate()
}

const stopAnimation = () => {
  isAnimating.value = false
  if (animationId.value) {
    cancelAnimationFrame(animationId.value)
    animationId.value = null
  }
}

const pauseAnimation = () => {
  isPaused.value = true
}

const resumeAnimation = () => {
  if (isAnimating.value) {
    isPaused.value = false
    startAnimation()
  }
}

const toggleAnimation = () => {
  if (isAnimating.value) {
    stopAnimation()
  } else {
    startAnimation()
  }
}

const handleNewsClick = (newsItem: Article) => {
  // Track click
  if (window.gtag) {
    window.gtag('event', 'breaking_news_click', {
      article_id: newsItem.id,
      article_title: newsItem.title
    })
  }

  emit('news-click', newsItem)
}

// Auto-start animation
onMounted(() => {
  nextTick(() => {
    if (props.autoPlay && props.news.length > 0) {
      startAnimation()
    }
  })
})

// Visibility change handler
const handleVisibilityChange = () => {
  if (document.visibilityState === 'visible' && props.autoPlay) {
    startAnimation()
  } else {
    stopAnimation()
  }
}

// Cleanup
onUnmounted(() => {
  stopAnimation()
  document.removeEventListener('visibilitychange', handleVisibilityChange)
})

// Watch for news changes
watch(() => props.news, (newNews) => {
  if (newNews.length > 0 && props.autoPlay) {
    nextTick(() => {
      startAnimation()
    })
  } else {
    stopAnimation()
  }
}, { immediate: true })

// Auto-restart animation when window becomes visible
document.addEventListener('visibilitychange', handleVisibilityChange)
</script>

<style scoped>
.breaking-news-ticker {
  position: relative;
  z-index: 50;
  background: linear-gradient(90deg, #eab308 0%, #f59e0b 100%);
}

.ticker-container {
  mask-image: linear-gradient(
    to right,
    transparent 0%,
    black 10%,
    black 90%,
    transparent 100%
  );
  -webkit-mask-image: linear-gradient(
    to right,
    transparent 0%,
    black 10%,
    black 90%,
    transparent 100%
  );
}

.ticker-content {
  animation-duration: v-bind(animationDuration);
  animation-timing-function: linear;
  animation-iteration-count: infinite;
  animation-fill-mode: forwards;
}

.animate-scroll {
  animation-name: scroll-left;
}

@keyframes scroll-left {
  0% {
    transform: translateX(0%);
  }
  100% {
    transform: translateX(-50%);
  }
}

.ticker-item {
  display: inline-flex;
  align-items: center;
  flex-shrink: 0;
}

.control-btn {
  transition: all 0.2s ease;
  background: rgba(0, 0, 0, 0.1);
}

.control-btn:hover {
  background: rgba(0, 0, 0, 0.2);
  transform: scale(1.05);
}

.progress-bar {
  background: rgba(0, 0, 0, 0.1);
}

.progress-fill {
  background: linear-gradient(90deg, #dc2626 0%, #991b1b 100%);
}

/* Responsive adjustments */
@media (max-width: 767px) {
  .breaking-label {
    margin-right: 1rem;
  }
  
  .breaking-label > div {
    padding: 0.5rem 0.75rem;
    font-size: 0.75rem;
  }
  
  .ticker-controls {
    margin-left: 0.5rem;
  }
}

@media (max-width: 640px) {
  .ticker-item span {
    font-size: 0.875rem;
  }
  
  .breaking-label > div {
    padding: 0.375rem 0.5rem;
    font-size: 0.6875rem;
  }
}

/* Hover effects */
.ticker-item:hover {
  transform: translateY(-1px);
}

/* Pause animation on hover */
.ticker-content:hover.animate-scroll {
  animation-play-state: paused;
}

/* Accessibility */
@media (prefers-reduced-motion: reduce) {
  .ticker-content {
    animation: none !important;
  }
  
  .animate-pulse {
    animation: none !important;
  }
}

/* High contrast mode */
@media (prefers-contrast: high) {
  .breaking-news-ticker {
    background: #ffff00 !important;
    color: #000000 !important;
  }
  
  .breaking-label > div {
    background: #000000 !important;
    color: #ffffff !important;
  }
}
</style>