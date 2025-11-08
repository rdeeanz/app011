// Core Types
export interface User {
  id: number
  name: string
  email: string
  avatar?: string
  bio?: string
  role: 'admin' | 'editor' | 'author' | 'user'
  verified_at?: string
  created_at: string
  updated_at: string
  followers_count?: number
  following_count?: number
  articles_count?: number
}

export interface Category {
  id: number
  name: string
  slug: string
  description?: string
  icon?: string
  color?: string
  parent_id?: number
  sort_order: number
  is_active: boolean
  meta_title?: string
  meta_description?: string
  articles_count?: number
  created_at: string
  updated_at: string
  parent?: Category
  children?: Category[]
  full_name?: string
  level?: number
}

export interface Tag {
  id: number
  name: string
  slug: string
  description?: string
  color?: string
  is_trending: boolean
  trending_score: number
  usage_count: number
  created_at: string
  updated_at: string
  display_name?: string
}

export interface Media {
  id: number
  filename: string
  original_name: string
  mime_type: string
  file_size: number
  file_path: string
  alt_text?: string
  caption?: string
  metadata?: Record<string, any>
  created_at: string
  updated_at: string
  url?: string
  thumbnail_url?: string
  is_image?: boolean
  file_size_human?: string
}

export interface Article {
  id: number
  title: string
  slug: string
  excerpt?: string
  content: string
  content_type: 'text' | 'video' | 'gallery' | 'live'
  status: 'draft' | 'published' | 'archived'
  editorial_status: 'draft' | 'pending' | 'approved' | 'rejected'
  featured_image?: string
  featured_image_caption?: string
  is_featured: boolean
  is_breaking: boolean
  is_trending: boolean
  allow_comments: boolean
  published_at?: string
  scheduled_at?: string
  views_count: number
  likes_count: number
  shares_count: number
  comments_count: number
  bookmarks_count: number
  engagement_score: number
  read_time?: number
  word_count?: number
  meta_title?: string
  meta_description?: string
  meta_keywords?: string
  created_at: string
  updated_at: string
  
  // Relationships
  author: User
  editor?: User
  reviewer?: User
  category?: Category
  tags?: Tag[]
  media?: Media[]
  comments?: Comment[]
  
  // Computed/Virtual attributes
  url?: string
  reading_time?: string
  time_ago?: string
  is_bookmarked?: boolean
  is_liked?: boolean
}

export interface Comment {
  id: number
  content: string
  status: 'pending' | 'approved' | 'rejected' | 'spam'
  parent_id?: number
  likes_count: number
  dislikes_count: number
  reports_count: number
  is_pinned: boolean
  created_at: string
  updated_at: string
  
  // Relationships
  user: User
  article: Article
  parent?: Comment
  replies?: Comment[]
  
  // Computed/Virtual attributes
  time_ago?: string
  is_liked?: boolean
  can_edit?: boolean
  can_delete?: boolean
  depth?: number
}

export interface Analytics {
  id: number
  event_type: string
  event_data?: Record<string, any>
  user_id?: number
  session_id?: string
  ip_address?: string
  user_agent?: string
  referer?: string
  created_at: string
}

// API Response Types
export interface ApiResponse<T> {
  data: T
  message?: string
  status: 'success' | 'error'
}

export interface PaginatedResponse<T> {
  data: T[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number
    to: number
  }
  links: {
    first: string
    last: string
    prev?: string
    next?: string
  }
}

// Form Types
export interface ArticleFormData {
  title: string
  excerpt?: string
  content: string
  content_type: Article['content_type']
  category_id?: number
  tag_ids?: number[]
  featured_image?: string
  is_featured?: boolean
  is_breaking?: boolean
  allow_comments?: boolean
  scheduled_at?: string
  meta_title?: string
  meta_description?: string
  meta_keywords?: string
}

export interface CommentFormData {
  content: string
  parent_id?: number
}

export interface UserFormData {
  name: string
  email: string
  password?: string
  bio?: string
  avatar?: File
}

// Filter Types
export interface ArticleFilters {
  category?: number
  tags?: number[]
  author?: number
  status?: Article['status']
  content_type?: Article['content_type']
  is_featured?: boolean
  is_breaking?: boolean
  date_from?: string
  date_to?: string
  search?: string
  sort?: 'newest' | 'oldest' | 'popular' | 'trending' | 'title'
  per_page?: number
  page?: number
}

export interface CommentFilters {
  article_id?: number
  user_id?: number
  status?: Comment['status']
  sort?: 'newest' | 'oldest' | 'likes'
  per_page?: number
  page?: number
}

// Component Props Types
export interface ComponentSize {
  size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl'
}

export interface ComponentVariant {
  variant?: 'primary' | 'secondary' | 'success' | 'warning' | 'error' | 'ghost'
}

// Event Types
export interface AnalyticsEvent {
  event: string
  data?: Record<string, any>
  user_id?: number
  timestamp?: string
}

// Navigation Types
export interface BreadcrumbItem {
  label: string
  href?: string
  active?: boolean
}

export interface MenuItem {
  id: string
  label: string
  href?: string
  icon?: string
  children?: MenuItem[]
  active?: boolean
  external?: boolean
}

// Settings Types
export interface SiteSettings {
  site_name: string
  site_description: string
  site_logo?: string
  site_favicon?: string
  social_links?: Record<string, string>
  analytics_id?: string
  ads_enabled?: boolean
  comments_enabled?: boolean
  newsletter_enabled?: boolean
}

// Notification Types
export interface Notification {
  id: string
  type: 'info' | 'success' | 'warning' | 'error'
  title: string
  message?: string
  duration?: number
  persistent?: boolean
  actions?: NotificationAction[]
}

export interface NotificationAction {
  label: string
  action: () => void
  style?: 'primary' | 'secondary'
}

// Store State Types
export interface AppState {
  user?: User
  isAuthenticated: boolean
  loading: boolean
  notifications: Notification[]
  siteSettings: SiteSettings
}

export interface ArticleState {
  articles: Article[]
  currentArticle?: Article
  loading: boolean
  error?: string
  pagination: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

// Utility Types
export type DeepPartial<T> = {
  [P in keyof T]?: T[P] extends object ? DeepPartial<T[P]> : T[P]
}

export type Optional<T, K extends keyof T> = Omit<T, K> & Partial<Pick<T, K>>

export type RequiredFields<T, K extends keyof T> = T & Required<Pick<T, K>>

// API Endpoints
export type ApiEndpoint = 
  | 'articles'
  | 'categories' 
  | 'tags'
  | 'comments'
  | 'users'
  | 'auth'
  | 'media'
  | 'analytics'
  | 'settings'

// Error Types
export interface ApiError {
  message: string
  errors?: Record<string, string[]>
  status_code: number
}

export interface ValidationError {
  field: string
  message: string
}

// Search Types
export interface SearchResult {
  articles: Article[]
  categories: Category[]
  tags: Tag[]
  users: User[]
  total: number
  query: string
  took: number
}

export interface SearchFilters {
  query: string
  type?: 'all' | 'articles' | 'categories' | 'tags' | 'users'
  category?: number
  date_range?: 'day' | 'week' | 'month' | 'year'
  sort?: 'relevance' | 'date' | 'popularity'
}

// Social Sharing
export interface ShareData {
  title: string
  url: string
  description?: string
  image?: string
  hashtags?: string[]
}

export interface SocialPlatform {
  name: string
  icon: string
  shareUrl: (data: ShareData) => string
  color: string
}

// Statistics Types
export interface DashboardStats {
  total_articles: number
  published_articles: number
  total_views: number
  total_comments: number
  total_users: number
  engagement_rate: number
  growth_rate: number
}

export interface ArticleStats {
  views: { date: string; count: number }[]
  likes: { date: string; count: number }[]
  comments: { date: string; count: number }[]
  shares: { date: string; count: number }[]
}

// Ad/Monetization Types
export interface AdZone {
  id: string
  name: string
  position: 'header' | 'sidebar' | 'content' | 'footer'
  size: 'banner' | 'rectangle' | 'skyscraper' | 'square'
  content?: string
  is_active: boolean
}

// Weather Widget Types (if used)
export interface WeatherData {
  location: string
  temperature: number
  condition: string
  icon: string
  humidity: number
  wind_speed: number
  forecast?: {
    date: string
    high: number
    low: number
    condition: string
    icon: string
  }[]
}

export default {}