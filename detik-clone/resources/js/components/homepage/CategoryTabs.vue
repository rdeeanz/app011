<template>
  <div class="category-tabs">
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-2xl font-bold text-gray-900">Berita Terkini</h2>
      
      <!-- Desktop: All Categories Button -->
      <button
        v-if="!isMobile"
        @click="$emit('category-change', null)"
        class="text-sm text-red-600 hover:text-red-700 font-medium"
      >
        Lihat Semua Kategori →
      </button>
    </div>

    <!-- Desktop Tabs -->
    <div v-if="!isMobile" class="hidden md:block">
      <nav class="flex space-x-1 bg-gray-100 rounded-lg p-1">
        <!-- All Articles Tab -->
        <button
          @click="handleCategoryClick(null)"
          :class="[
            'px-4 py-2 rounded-md text-sm font-medium transition-all duration-200',
            activeCategory === null
              ? 'bg-white text-red-600 shadow-sm'
              : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
          ]"
        >
          Semua
        </button>

        <!-- Category Tabs -->
        <button
          v-for="category in visibleCategories"
          :key="category.id"
          @click="handleCategoryClick(category.id)"
          :class="[
            'px-4 py-2 rounded-md text-sm font-medium transition-all duration-200 flex items-center',
            activeCategory === category.id
              ? 'bg-white text-red-600 shadow-sm'
              : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
          ]"
        >
          <CategoryIcon 
            :icon="category.icon" 
            class="w-4 h-4 mr-2"
          />
          {{ category.name }}
          
          <!-- Article count badge -->
          <span 
            v-if="category.articles_count && category.articles_count > 0"
            class="ml-2 px-2 py-0.5 text-xs rounded-full"
            :class="[
              activeCategory === category.id
                ? 'bg-red-100 text-red-700'
                : 'bg-gray-200 text-gray-600'
            ]"
          >
            {{ formatNumber(category.articles_count) }}
          </span>
        </button>

        <!-- More Categories Dropdown -->
        <div v-if="hasMoreCategories" class="relative">
          <button
            @click="toggleMoreDropdown"
            :class="[
              'px-4 py-2 rounded-md text-sm font-medium transition-all duration-200 flex items-center',
              showMoreDropdown
                ? 'bg-white text-red-600 shadow-sm'
                : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
            ]"
          >
            Lainnya
            <ChevronDownIcon 
              class="w-4 h-4 ml-1 transition-transform duration-200"
              :class="{ 'transform rotate-180': showMoreDropdown }"
            />
          </button>

          <!-- Dropdown Menu -->
          <Transition name="dropdown">
            <div
              v-if="showMoreDropdown"
              class="absolute top-full left-0 mt-1 w-56 bg-white rounded-lg shadow-lg border border-gray-200 py-2 z-10"
            >
              <button
                v-for="category in hiddenCategories"
                :key="category.id"
                @click="handleCategoryClick(category.id)"
                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 hover:text-red-600 flex items-center"
              >
                <CategoryIcon 
                  :icon="category.icon" 
                  class="w-4 h-4 mr-3"
                />
                {{ category.name }}
                <span 
                  v-if="category.articles_count && category.articles_count > 0"
                  class="ml-auto text-xs text-gray-500"
                >
                  {{ formatNumber(category.articles_count) }}
                </span>
              </button>
            </div>
          </Transition>
        </div>
      </nav>
    </div>

    <!-- Mobile Horizontal Scroll -->
    <div v-if="isMobile" class="md:hidden">
      <div class="flex space-x-3 overflow-x-auto pb-2 scrollbar-hide">
        <!-- All Articles Chip -->
        <button
          @click="handleCategoryClick(null)"
          :class="[
            'flex-shrink-0 px-4 py-2 rounded-full text-sm font-medium transition-all duration-200',
            activeCategory === null
              ? 'bg-red-600 text-white shadow-md'
              : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
          ]"
        >
          Semua
        </button>

        <!-- Category Chips -->
        <button
          v-for="category in categories"
          :key="category.id"
          @click="handleCategoryClick(category.id)"
          :class="[
            'flex-shrink-0 px-4 py-2 rounded-full text-sm font-medium transition-all duration-200 flex items-center',
            activeCategory === category.id
              ? 'bg-red-600 text-white shadow-md'
              : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
          ]"
        >
          <CategoryIcon 
            :icon="category.icon" 
            class="w-3 h-3 mr-1"
          />
          {{ category.name }}
        </button>
      </div>
    </div>

    <!-- Selected Category Info -->
    <div v-if="selectedCategory" class="mt-4 p-4 bg-red-50 rounded-lg border border-red-100">
      <div class="flex items-start justify-between">
        <div class="flex items-center">
          <CategoryIcon 
            :icon="selectedCategory.icon" 
            class="w-6 h-6 mr-3 text-red-600"
          />
          <div>
            <h3 class="font-semibold text-red-900">{{ selectedCategory.name }}</h3>
            <p v-if="selectedCategory.description" class="text-sm text-red-700 mt-1">
              {{ selectedCategory.description }}
            </p>
          </div>
        </div>

        <button
          @click="$emit('category-change', null)"
          class="text-red-600 hover:text-red-700 p-1"
          title="Clear filter"
        >
          <XMarkIcon class="w-5 h-5" />
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="animate-pulse">
      <div class="flex space-x-2 mb-4">
        <div class="h-10 bg-gray-200 rounded-lg w-20"></div>
        <div class="h-10 bg-gray-200 rounded-lg w-24"></div>
        <div class="h-10 bg-gray-200 rounded-lg w-28"></div>
        <div class="h-10 bg-gray-200 rounded-lg w-22"></div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { ChevronDownIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import { useBreakpoints } from '@vueuse/core'
import CategoryIcon from '../common/CategoryIcon.vue'
import { formatNumber } from '@/utils/formatters'
import type { Category } from '@/types'

// Props
interface Props {
  categories: Category[]
  activeCategory: number | null
  loading?: boolean
  maxVisibleCategories?: number
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  maxVisibleCategories: 6
})

// Emits
const emit = defineEmits<{
  'category-change': [categoryId: number | null]
}>()

// Composables
const breakpoints = useBreakpoints({
  mobile: 640,
  tablet: 768,
  desktop: 1024,
})

// Reactive state
const showMoreDropdown = ref(false)

// Computed
const isMobile = computed(() => breakpoints.smaller('tablet').value)

const visibleCategories = computed(() => {
  return props.categories.slice(0, props.maxVisibleCategories)
})

const hiddenCategories = computed(() => {
  return props.categories.slice(props.maxVisibleCategories)
})

const hasMoreCategories = computed(() => {
  return props.categories.length > props.maxVisibleCategories
})

const selectedCategory = computed(() => {
  if (!props.activeCategory) return null
  return props.categories.find(cat => cat.id === props.activeCategory) || null
})

// Methods
const handleCategoryClick = (categoryId: number | null) => {
  // Close dropdown if open
  showMoreDropdown.value = false
  
  // Emit category change
  emit('category-change', categoryId)

  // Track category selection
  if (window.gtag) {
    window.gtag('event', 'category_select', {
      category_id: categoryId,
      category_name: categoryId 
        ? props.categories.find(c => c.id === categoryId)?.name || 'unknown'
        : 'all'
    })
  }
}

const toggleMoreDropdown = () => {
  showMoreDropdown.value = !showMoreDropdown.value
}

const closeDropdown = (event: Event) => {
  const target = event.target as HTMLElement
  if (!target.closest('.relative')) {
    showMoreDropdown.value = false
  }
}

// Lifecycle
onMounted(() => {
  document.addEventListener('click', closeDropdown)
})

onUnmounted(() => {
  document.removeEventListener('click', closeDropdown)
})
</script>

<style scoped>
/* Scrollbar hiding for mobile horizontal scroll */
.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

.scrollbar-hide::-webkit-scrollbar {
  display: none;
}

/* Dropdown transition */
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.2s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

/* Tab hover effects */
.category-tabs button {
  transition: all 0.2s ease;
}

.category-tabs button:hover {
  transform: translateY(-1px);
}

/* Active tab glow effect */
.category-tabs button.bg-white {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* Mobile chip styling */
.category-tabs .bg-red-600 {
  box-shadow: 0 2px 8px rgba(220, 38, 38, 0.3);
}

/* Focus styles for accessibility */
.category-tabs button:focus {
  outline: 2px solid #dc2626;
  outline-offset: 2px;
}

/* Responsive adjustments */
@media (max-width: 767px) {
  .category-tabs h2 {
    font-size: 1.25rem;
  }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  .category-tabs button {
    border: 1px solid currentColor;
  }
  
  .bg-gray-100 {
    background-color: #f0f0f0 !important;
  }
  
  .bg-white {
    background-color: #ffffff !important;
    border-color: #000000 !important;
  }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .category-tabs button {
    transition: none !important;
  }
  
  .dropdown-enter-active,
  .dropdown-leave-active {
    transition: none !important;
  }
}

/* Loading skeleton animation */
@keyframes skeleton-loading {
  0% {
    background-position: -200px 0;
  }
  100% {
    background-position: calc(200px + 100%) 0;
  }
}

.animate-pulse > div {
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200px 100%;
  animation: skeleton-loading 1.5s infinite;
}
</style>