<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

/**
 * Category Model - Advanced News Category Management
 * 
 * Features:
 * - Hierarchical categories with unlimited nesting
 * - SEO optimization with slugs and meta data
 * - Smart sorting and organization
 * - Rich content management capabilities
 * - Performance optimized with caching
 * - Analytics and popularity tracking
 */
class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'icon',
        'parent_id',
        'sort_order',
        'is_active',
        'is_featured',
        'articles_count',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'og_image',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
        'articles_count' => 'integer',
    ];

    protected $attributes = [
        'color' => '#3B82F6',
        'sort_order' => 0,
        'is_active' => true,
        'is_featured' => false,
        'articles_count' => 0,
    ];

    protected $appends = [
        'full_name',
        'level',
        'article_count',
        'url',
        'breadcrumb',
    ];

    // ===== RELATIONSHIPS =====

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function publishedArticles(): HasMany
    {
        return $this->articles()->published();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function activeChildren(): HasMany
    {
        return $this->children()->where('is_active', true)->orderBy('sort_order');
    }

    // ===== ACCESSORS =====

    public function getFullNameAttribute(): string
    {
        $names = collect([$this->name]);
        $parent = $this->parent;
        
        while ($parent) {
            $names->prepend($parent->name);
            $parent = $parent->parent;
        }
        
        return $names->implode(' > ');
    }

    public function getArticleCountAttribute(): int
    {
        return Cache::remember("category_{$this->id}_article_count", 3600, function () {
            return $this->publishedArticles()->count();
        });
    }

    public function getLevelAttribute(): int
    {
        $level = 0;
        $parent = $this->parent;
        
        while ($parent) {
            $level++;
            $parent = $parent->parent;
        }
        
        return $level;
    }

    public function getUrlAttribute(): string
    {
        return route('categories.show', $this->slug);
    }

    public function getBreadcrumbAttribute(): array
    {
        $breadcrumb = [];
        $category = $this;
        
        while ($category) {
            array_unshift($breadcrumb, [
                'name' => $category->name,
                'url' => $category->url,
                'id' => $category->id
            ]);
            $category = $category->parent;
        }
        
        return $breadcrumb;
    }

    // ===== QUERY SCOPES =====

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeParent(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id')->orderBy('sort_order');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeWithArticleCount(Builder $query): Builder
    {
        return $query->withCount(['publishedArticles as articles_count']);
    }

    public function scopePopular(Builder $query): Builder
    {
        return $query->withCount('publishedArticles')
                    ->orderBy('published_articles_count', 'desc');
    }

    public function scopeHasArticles(Builder $query): Builder
    {
        return $query->whereHas('publishedArticles');
    }

    // ===== HELPER METHODS =====

    public function updateArticlesCount(): void
    {
        $count = $this->publishedArticles()->count();
        $this->update(['articles_count' => $count]);
        Cache::forget("category_{$this->id}_article_count");
    }

    public function hasArticles(): bool
    {
        return $this->publishedArticles()->exists();
    }

    public function getAllChildren()
    {
        // Use a single recursive CTE query to get all descendants
        $sql = "
            WITH RECURSIVE category_tree AS (
                SELECT id, name, parent_id, 0 as depth
                FROM categories 
                WHERE parent_id = ?
                
                UNION ALL
                
                SELECT c.id, c.name, c.parent_id, ct.depth + 1
                FROM categories c
                INNER JOIN category_tree ct ON c.parent_id = ct.id
            )
            SELECT * FROM category_tree ORDER BY depth, name
        ";
        
        $results = \DB::select($sql, [$this->id]);
        
        if (empty($results)) {
            return collect();
        }
        
        // Hydrate the results into Category models
        $categoryIds = collect($results)->pluck('id')->toArray();
        return Category::whereIn('id', $categoryIds)->get();
    }

    public function getAllDescendants()
    {
        return $this->getAllChildren();
    }

    public function getAncestors()
    {
        // Use a single recursive CTE query to get all ancestors
        $sql = "
            WITH RECURSIVE category_ancestors AS (
                SELECT id, name, parent_id, 0 as depth
                FROM categories 
                WHERE id = ?
                
                UNION ALL
                
                SELECT c.id, c.name, c.parent_id, ca.depth - 1
                FROM categories c
                INNER JOIN category_ancestors ca ON c.id = ca.parent_id
            )
            SELECT * FROM category_ancestors 
            WHERE id != ? 
            ORDER BY depth DESC
        ";
        
        $results = \DB::select($sql, [$this->id, $this->id]);
        
        if (empty($results)) {
            return collect();
        }
        
        // Hydrate the results into Category models
        $categoryIds = collect($results)->pluck('id')->toArray();
        return Category::whereIn('id', $categoryIds)->get();
    }

    public function isRoot(): bool
    {
        return is_null($this->parent_id);
    }

    public function isLeaf(): bool
    {
        return $this->children()->count() === 0;
    }

    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    public function isDescendantOf(Category $category): bool
    {
        $parent = $this->parent;
        
        while ($parent) {
            if ($parent->id === $category->id) {
                return true;
            }
            $parent = $parent->parent;
        }
        
        return false;
    }

    public function isAncestorOf(Category $category): bool
    {
        return $category->isDescendantOf($this);
    }

    public function getTreePath(): string
    {
        return $this->getAncestors()
                   ->pluck('name')
                   ->push($this->name)
                   ->implode(' / ');
    }

    public function getAllDescendantIds(): array
    {
        $tableName = $this->getTable();
        $rootId = $this->id;
        
        $query = "
            WITH RECURSIVE descendant_tree AS (
                SELECT id FROM {$tableName} WHERE parent_id = ?
                UNION ALL
                SELECT c.id FROM {$tableName} c
                INNER JOIN descendant_tree dt ON c.parent_id = dt.id
            )
            SELECT id FROM descendant_tree
        ";
        
        $results = DB::select($query, [$rootId]);
        
        return array_map(function($row) {
            return (int) $row->id;
        }, $results);
    }

    public function getLatestArticles(int $limit = 5)
    {
        return $this->publishedArticles()
                   ->latest('published_at')
                   ->limit($limit)
                   ->get();
    }

    public function getFeaturedArticles(int $limit = 3)
    {
        return $this->publishedArticles()
                   ->where('is_featured', true)
                   ->latest('published_at')
                   ->limit($limit)
                   ->get();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Generate a unique slug for the category
     */
    private function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $count = 1;

        // Check for existing slugs and append number if needed
        while (self::where('slug', $slug)->where('id', '!=', $this->id ?? 0)->exists()) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        return $slug;
    }

    // ===== BOOT METHOD =====

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function (Category $category) {
            if (empty($category->slug)) {
                $category->slug = $category->generateUniqueSlug($category->name);
            }
            
            // Auto-generate SEO fields if not provided
            if (empty($category->seo_title)) {
                $category->seo_title = $category->name;
            }
            
            if (empty($category->seo_description) && $category->description) {
                $category->seo_description = Str::limit($category->description, 160);
            }
        });
        
        static::updating(function (Category $category) {
            if ($category->isDirty('name')) {
                if (empty($category->slug) || $category->slug === Str::slug($category->getOriginal('name'))) {
                    $category->slug = $category->generateUniqueSlug($category->name);
                }
                
                if (empty($category->seo_title)) {
                    $category->seo_title = $category->name;
                }
            }
        });
        
        static::saved(function (Category $category) {
            // Clear related caches when category is saved
            Cache::forget("category_{$category->id}_article_count");
            Cache::forget('featured_categories');
            Cache::forget('active_categories');
        });
    }
}