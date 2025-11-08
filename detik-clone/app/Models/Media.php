<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Media Model - Advanced Media Management System
 * 
 * Features:
 * - Polymorphic media attachments for any model
 * - Advanced file type detection and validation
 * - Automatic thumbnail and variant generation
 * - SEO optimization with alt text and captions
 * - Cloud storage support with CDN integration
 * - Performance optimized with caching
 * - Media analytics and usage tracking
 * - Bulk operations and batch processing
 */
class Media extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'mediable_type',
        'mediable_id',
        'collection_name',
        'type',
        'filename',
        'original_filename',
        'path',
        'url',
        'disk',
        'mime_type',
        'size',
        'width',
        'height',
        'duration',
        'alt_text',
        'caption',
        'description',
        'title',
        'metadata',
        'is_featured',
        'is_processed',
        'sort_order',
        'uploaded_by',
        'views_count',
        'downloads_count',
        'processing_status',
        'variants',
    ];

    protected $casts = [
        'metadata' => 'array',
        'variants' => 'array',
        'is_featured' => 'boolean',
        'is_processed' => 'boolean',
        'size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'duration' => 'integer',
        'sort_order' => 'integer',
        'views_count' => 'integer',
        'downloads_count' => 'integer',
    ];

    protected $attributes = [
        'is_featured' => false,
        'is_processed' => false,
        'sort_order' => 0,
        'views_count' => 0,
        'downloads_count' => 0,
        'processing_status' => 'pending',
    ];

    protected $appends = [
        'full_url',
        'public_url',
        'file_size_human',
        'dimensions',
        'aspect_ratio',
        'is_image',
        'is_video',
        'is_audio',
        'is_document',
    ];

    // Media type constants
    const TYPE_IMAGE = 'image';
    const TYPE_VIDEO = 'video';
    const TYPE_AUDIO = 'audio';
    const TYPE_DOCUMENT = 'document';
    const TYPE_ARCHIVE = 'archive';
    const TYPE_OTHER = 'other';

    // Processing status constants
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    // ===== RELATIONSHIPS =====

    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // ===== ACCESSORS =====

    public function getFullUrlAttribute(): string
    {
        if ($this->url) {
            return $this->url;
        }
        
        return Storage::disk($this->disk)->url($this->path);
    }

    public function getPublicUrlAttribute(): string
    {
        // Return CDN URL if available
        if ($cdnUrl = $this->getCdnUrl()) {
            return $cdnUrl;
        }
        
        return $this->full_url;
    }

    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getDimensionsAttribute(): ?string
    {
        if ($this->width && $this->height) {
            return $this->width . 'x' . $this->height;
        }
        
        return null;
    }

    public function getAspectRatioAttribute(): ?float
    {
        if ($this->width && $this->height && $this->height > 0) {
            return round($this->width / $this->height, 2);
        }
        
        return null;
    }

    public function getIsImageAttribute(): bool
    {
        return $this->type === self::TYPE_IMAGE;
    }

    public function getIsVideoAttribute(): bool
    {
        return $this->type === self::TYPE_VIDEO;
    }

    public function getIsAudioAttribute(): bool
    {
        return $this->type === self::TYPE_AUDIO;
    }

    public function getIsDocumentAttribute(): bool
    {
        return $this->type === self::TYPE_DOCUMENT;
    }

    // ===== QUERY SCOPES =====

    public function scopeImages(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_IMAGE);
    }

    public function scopeVideos(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_VIDEO);
    }

    public function scopeAudios(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_AUDIO);
    }

    public function scopeDocuments(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_DOCUMENT);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeProcessed(Builder $query): Builder
    {
        return $query->where('is_processed', true);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('processing_status', self::STATUS_PENDING);
    }

    public function scopeProcessing(Builder $query): Builder
    {
        return $query->where('processing_status', self::STATUS_PROCESSING);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('processing_status', self::STATUS_COMPLETED);
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('processing_status', self::STATUS_FAILED);
    }

    public function scopeByCollection(Builder $query, string $collection): Builder
    {
        return $query->where('collection_name', $collection);
    }

    public function scopeByMimeType(Builder $query, string $mimeType): Builder
    {
        return $query->where('mime_type', $mimeType);
    }

    public function scopeByUploader(Builder $query, $userId): Builder
    {
        return $query->where('uploaded_by', $userId);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('created_at');
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopePopular(Builder $query): Builder
    {
        return $query->orderBy('views_count', 'desc')
                    ->orderBy('downloads_count', 'desc');
    }

    // ===== HELPER METHODS =====

    public function getVariant(string $variant): ?string
    {
        $variants = $this->variants ?? [];
        
        if (isset($variants[$variant])) {
            return Storage::disk($this->disk)->url($variants[$variant]);
        }
        
        return null;
    }

    public function getThumbnail(string $size = 'medium'): ?string
    {
        if (!$this->is_image) {
            return $this->getDefaultThumbnail();
        }
        
        return $this->getVariant("thumbnail_{$size}") ?: $this->full_url;
    }

    public function getDefaultThumbnail(): string
    {
        $iconMap = [
            self::TYPE_VIDEO => '/icons/video.svg',
            self::TYPE_AUDIO => '/icons/audio.svg',
            self::TYPE_DOCUMENT => '/icons/document.svg',
            self::TYPE_ARCHIVE => '/icons/archive.svg',
        ];
        
        return $iconMap[$this->type] ?? '/icons/file.svg';
    }

    public function getCdnUrl(): ?string
    {
        $cdnBase = config('media.cdn_base_url');
        
        if ($cdnBase && $this->path) {
            return rtrim($cdnBase, '/') . '/' . ltrim($this->path, '/');
        }
        
        return null;
    }

    public function incrementViews(): void
    {
        $this->increment('views_count');
        $this->clearRelatedCaches();
    }

    public function incrementDownloads(): void
    {
        $this->increment('downloads_count');
        $this->clearRelatedCaches();
    }

    public function markAsProcessing(): bool
    {
        return $this->update(['processing_status' => self::STATUS_PROCESSING]);
    }

    public function markAsCompleted(): bool
    {
        return $this->update([
            'processing_status' => self::STATUS_COMPLETED,
            'is_processed' => true,
        ]);
    }

    public function markAsFailed(): bool
    {
        return $this->update(['processing_status' => self::STATUS_FAILED]);
    }

    public function feature(): bool
    {
        $result = $this->update(['is_featured' => true]);
        if ($result) {
            $this->clearRelatedCaches();
        }
        return $result;
    }

    public function unfeature(): bool
    {
        $result = $this->update(['is_featured' => false]);
        if ($result) {
            $this->clearRelatedCaches();
        }
        return $result;
    }

    public function toggleFeature(): bool
    {
        return $this->is_featured ? $this->unfeature() : $this->feature();
    }

    public function exists(): bool
    {
        return Storage::disk($this->disk)->exists($this->path);
    }

    public function getContents(): ?string
    {
        if (!$this->exists()) {
            return null;
        }
        
        return Storage::disk($this->disk)->get($this->path);
    }

    public function move(string $newPath): bool
    {
        $oldPath = $this->path;
        
        if (Storage::disk($this->disk)->move($oldPath, $newPath)) {
            $this->update(['path' => $newPath]);
            $this->clearRelatedCaches();
            return true;
        }
        
        return false;
    }

    public function copy(string $newPath): ?self
    {
        if (Storage::disk($this->disk)->copy($this->path, $newPath)) {
            $copy = $this->replicate();
            $copy->path = $newPath;
            $copy->save();
            
            return $copy;
        }
        
        return null;
    }

    public function delete(): bool
    {
        return \DB::transaction(function () {
            // Delete database record first
            $result = parent::delete();
            
            if ($result) {
                // Only delete files after successful DB deletion
                if ($this->exists()) {
                    Storage::disk($this->disk)->delete($this->path);
                }
                
                // Delete variants
                $variants = $this->variants ?? [];
                foreach ($variants as $variantPath) {
                    if (Storage::disk($this->disk)->exists($variantPath)) {
                        Storage::disk($this->disk)->delete($variantPath);
                    }
                }
                
                $this->clearRelatedCaches();
            }
            
            return $result;
        });
    }

    public function addVariant(string $name, string $path): void
    {
        $variants = $this->variants ?? [];
        $variants[$name] = $path;
        
        $this->update(['variants' => $variants]);
    }

    public function removeVariant(string $name): void
    {
        $variants = $this->variants ?? [];
        
        if (isset($variants[$name])) {
            $variantPath = $variants[$name];
            
            // Delete physical file
            if (Storage::disk($this->disk)->exists($variantPath)) {
                Storage::disk($this->disk)->delete($variantPath);
            }
            
            unset($variants[$name]);
            $this->update(['variants' => $variants]);
        }
    }

    public function getFileExtension(): string
    {
        return pathinfo($this->filename, PATHINFO_EXTENSION);
    }

    public function getBasename(): string
    {
        return pathinfo($this->filename, PATHINFO_FILENAME);
    }

    public function isExpired(): bool
    {
        $expiryDays = config('media.expiry_days', 365);
        return $this->created_at->diffInDays(now()) > $expiryDays;
    }

    public function generateSeoFields(): void
    {
        if (!$this->alt_text && $this->is_image) {
            $this->alt_text = $this->generateAltText();
        }
        
        if (!$this->title) {
            $this->title = $this->generateTitle();
        }
        
        // Don't call save() - let the creating event handle persistence
    }

    private function generateAltText(): string
    {
        $basename = $this->getBasename();
        return Str::title(str_replace(['-', '_'], ' ', $basename));
    }

    private function generateTitle(): string
    {
        return $this->original_filename ?: $this->filename;
    }

    private function clearRelatedCaches(): void
    {
        Cache::forget("media_{$this->id}");
        Cache::forget("popular_media");
        Cache::forget("recent_media");
        
        if ($this->mediable_type && $this->mediable_id) {
            $cacheKey = strtolower(class_basename($this->mediable_type)) . "_{$this->mediable_id}_media";
            Cache::forget($cacheKey);
        }
    }

    // ===== STATIC METHODS =====

    public static function getTypeFromMimeType(string $mimeType): string
    {
        $imageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        $videoTypes = ['video/mp4', 'video/mpeg', 'video/quicktime', 'video/webm'];
        $audioTypes = ['audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/mp3'];
        $documentTypes = ['application/pdf', 'application/msword', 'text/plain'];
        $archiveTypes = ['application/zip', 'application/x-rar', 'application/x-tar'];
        
        if (in_array($mimeType, $imageTypes)) {
            return self::TYPE_IMAGE;
        }
        
        if (in_array($mimeType, $videoTypes)) {
            return self::TYPE_VIDEO;
        }
        
        if (in_array($mimeType, $audioTypes)) {
            return self::TYPE_AUDIO;
        }
        
        if (in_array($mimeType, $documentTypes)) {
            return self::TYPE_DOCUMENT;
        }
        
        if (in_array($mimeType, $archiveTypes)) {
            return self::TYPE_ARCHIVE;
        }
        
        return self::TYPE_OTHER;
    }

    public static function cleanupExpired(): int
    {
        $expired = static::where('created_at', '<', now()->subDays(config('media.expiry_days', 365)))
                        ->get();
        
        $count = 0;
        foreach ($expired as $media) {
            $media->delete();
            $count++;
        }
        
        return $count;
    }

    public static function cleanupOrphaned(): int
    {
        $orphaned = static::whereNull('mediable_id')
                          ->orWhereNull('mediable_type')
                          ->get();
        
        $count = 0;
        foreach ($orphaned as $media) {
            $media->delete();
            $count++;
        }
        
        return $count;
    }

    // ===== BOOT METHOD =====

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function (Media $media) {
            // Auto-detect media type from MIME type
            if (!$media->type && $media->mime_type) {
                $media->type = static::getTypeFromMimeType($media->mime_type);
            }
            
            // Generate URL if not provided
            if (!$media->url && $media->path) {
                $media->url = Storage::disk($media->disk)->url($media->path);
            }
            
            // Auto-generate SEO fields
            $media->generateSeoFields();
        });
        
        static::created(function (Media $media) {
            $media->clearRelatedCaches();
        });
        
        static::updated(function (Media $media) {
            $media->clearRelatedCaches();
        });
        
        static::deleted(function (Media $media) {
            $media->clearRelatedCaches();
        });
    }
}