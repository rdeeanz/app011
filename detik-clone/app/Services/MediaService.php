<?php

namespace App\Services;

use App\Models\Media;
use App\Models\Article;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class MediaService
{
    protected array $allowedImageTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    protected array $allowedVideoTypes = ['mp4', 'avi', 'mov', 'wmv', 'flv'];
    protected array $allowedDocumentTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];
    
    protected int $maxFileSize = 10485760; // 10MB
    protected int $maxImageSize = 5242880; // 5MB

    public function __construct()
    {
        // Initialize image manager if available
        if (class_exists(ImageManager::class)) {
            $this->imageManager = new ImageManager(new Driver());
        }
    }

    // ===== FILE UPLOAD METHODS =====

    /**
     * Upload any type of file
     */
    public function uploadFile(UploadedFile $file, $model, string $collection = 'default'): Media
    {
        $this->validateFile($file);
        
        $fileType = $this->determineFileType($file);
        
        return match($fileType) {
            'image' => $this->uploadImage($file, $model, $collection),
            'video' => $this->uploadVideo($file, $model, $collection),
            'document' => $this->uploadDocument($file, $model, $collection),
            default => throw new \InvalidArgumentException('Unsupported file type')
        };
    }

    /**
     * Upload image with thumbnail generation
     */
    public function uploadImage(UploadedFile $file, $model, string $collection = 'default'): Media
    {
        $this->validateImage($file);
        
        $filename = $this->generateFilename($file);
        $path = "media/images/{$collection}/" . date('Y/m/d');
        $fullPath = "{$path}/{$filename}";
        
        // Store original image
        $storedPath = $file->storeAs($path, $filename, 'public');
        
        // Get image dimensions
        $dimensions = getimagesize($file->getRealPath());
        $width = $dimensions[0] ?? null;
        $height = $dimensions[1] ?? null;
        
        // Create media record
        $media = $this->createMediaRecord($file, $model, [
            'type' => 'image',
            'path' => $storedPath,
            'width' => $width,
            'height' => $height,
        ]);
        
        // Generate thumbnails
        $this->generateThumbnails($storedPath);
        
        return $media;
    }

    /**
     * Upload video file
     */
    public function uploadVideo(UploadedFile $file, $model, string $collection = 'default'): Media
    {
        $this->validateVideo($file);
        
        $filename = $this->generateFilename($file);
        $path = "media/videos/{$collection}/" . date('Y/m/d');
        
        $storedPath = $file->storeAs($path, $filename, 'public');
        
        return $this->createMediaRecord($file, $model, [
            'type' => 'video',
            'path' => $storedPath,
        ]);
    }

    /**
     * Upload document file
     */
    public function uploadDocument(UploadedFile $file, $model, string $collection = 'default'): Media
    {
        $this->validateDocument($file);
        
        $filename = $this->generateFilename($file);
        $path = "media/documents/{$collection}/" . date('Y/m/d');
        
        $storedPath = $file->storeAs($path, $filename, 'public');
        
        return $this->createMediaRecord($file, $model, [
            'type' => 'document',
            'path' => $storedPath,
        ]);
    }

    // ===== THUMBNAIL GENERATION =====

    /**
     * Generate thumbnails for images
     */
    public function generateThumbnails(string $imagePath): array
    {
        if (!isset($this->imageManager)) {
            Log::warning('Image manipulation library not available');
            return [];
        }

        $thumbnails = [];
        $sizes = [
            'thumb' => ['width' => 150, 'height' => 150],
            'small' => ['width' => 300, 'height' => 200],
            'medium' => ['width' => 600, 'height' => 400],
            'large' => ['width' => 1200, 'height' => 800],
        ];
        
        $fullPath = Storage::disk('public')->path($imagePath);
        
        if (!file_exists($fullPath)) {
            return [];
        }
        
        $pathInfo = pathinfo($imagePath);
        $thumbsDir = $pathInfo['dirname'] . '/thumbs';
        
        // Create thumbs directory
        Storage::disk('public')->makeDirectory($thumbsDir);
        
        foreach ($sizes as $sizeName => $dimensions) {
            try {
                $image = $this->imageManager->read($fullPath);
                
                // Resize maintaining aspect ratio
                $image->cover($dimensions['width'], $dimensions['height']);
                
                // Save thumbnail
                $thumbnailPath = $thumbsDir . '/' . $pathInfo['filename'] . '_' . $sizeName . '.' . $pathInfo['extension'];
                $thumbnailFullPath = Storage::disk('public')->path($thumbnailPath);
                
                $image->save($thumbnailFullPath, 85); // 85% quality
                
                $thumbnails[$sizeName] = $thumbnailPath;
                
            } catch (\Exception $e) {
                Log::error("Thumbnail generation failed for size {$sizeName}", [
                    'error' => $e->getMessage(),
                    'image_path' => $imagePath
                ]);
            }
        }
        
        return $thumbnails;
    }

    // ===== MEDIA MANAGEMENT =====

    /**
     * Delete a media file and its thumbnails
     */
    public function deleteMedia(Media $media): bool
    {
        try {
            // Delete physical file
            if (Storage::disk($media->disk)->exists($media->path)) {
                Storage::disk($media->disk)->delete($media->path);
            }
            
            // Delete thumbnails if it's an image
            if ($media->type === 'image') {
                $this->deleteThumbnails($media->path);
            }
            
            // Delete database record
            return $media->delete();
            
        } catch (\Exception $e) {
            Log::error('Media deletion failed', [
                'media_id' => $media->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Delete file by path
     */
    public function deleteFile(string $path, string $disk = 'public'): bool
    {
        try {
            if (Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
                
                // If it's an image, delete thumbnails too
                $this->deleteThumbnails($path);
                
                return true;
            }
            return false;
        } catch (\Exception $e) {
            Log::error('File deletion failed', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Update media metadata
     */
    public function updateMediaMetadata(Media $media, array $metadata): Media
    {
        $currentMetadata = $media->metadata ?? [];
        $updatedMetadata = array_merge($currentMetadata, $metadata);
        
        $media->update(['metadata' => $updatedMetadata]);
        
        return $media;
    }

    /**
     * Optimize image for web
     */
    public function optimizeImage(string $imagePath): bool
    {
        if (!isset($this->imageManager)) {
            return false;
        }

        try {
            $fullPath = Storage::disk('public')->path($imagePath);
            
            if (!file_exists($fullPath)) {
                return false;
            }
            
            $image = $this->imageManager->read($fullPath);
            
            // Optimize for web (reduce quality if large file)
            $fileSize = filesize($fullPath);
            $quality = $fileSize > 1000000 ? 75 : 85; // Lower quality for files > 1MB
            
            // Convert to WebP if supported
            $pathInfo = pathinfo($imagePath);
            $webpPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.webp';
            $webpFullPath = Storage::disk('public')->path($webpPath);
            
            $image->toWebp($quality)->save($webpFullPath);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Image optimization failed', [
                'image_path' => $imagePath,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    // ===== UTILITY METHODS =====

    /**
     * Get media by model
     */
    public function getMediaForModel($model, string $collection = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = $model->media();
        
        if ($collection) {
            $query->where('collection_name', $collection);
        }
        
        return $query->orderBy('sort_order')->orderBy('created_at')->get();
    }

    /**
     * Get featured image for model
     */
    public function getFeaturedImage($model): ?Media
    {
        return $model->media()->where('is_featured', true)->first();
    }

    /**
     * Set featured image for model
     */
    public function setFeaturedImage($model, Media $media): bool
    {
        // Unfeature all current featured images
        $model->media()->update(['is_featured' => false]);
        
        // Set new featured image
        return $media->update(['is_featured' => true]);
    }

    /**
     * Bulk upload files
     */
    public function bulkUpload(array $files, $model, string $collection = 'default'): array
    {
        $uploadedMedia = [];
        
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                try {
                    $media = $this->uploadFile($file, $model, $collection);
                    $uploadedMedia[] = $media;
                } catch (\Exception $e) {
                    Log::error('Bulk upload file failed', [
                        'filename' => $file->getClientOriginalName(),
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
        
        return $uploadedMedia;
    }

    // ===== VALIDATION METHODS =====

    protected function validateFile(UploadedFile $file): void
    {
        if (!$file->isValid()) {
            throw new \InvalidArgumentException('Invalid file upload');
        }
        
        if ($file->getSize() > $this->maxFileSize) {
            throw new \InvalidArgumentException('File too large. Maximum size: ' . ($this->maxFileSize / 1024 / 1024) . 'MB');
        }
    }

    protected function validateImage(UploadedFile $file): void
    {
        $this->validateFile($file);
        
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $this->allowedImageTypes)) {
            throw new \InvalidArgumentException('Invalid image type. Allowed: ' . implode(', ', $this->allowedImageTypes));
        }
        
        if ($file->getSize() > $this->maxImageSize) {
            throw new \InvalidArgumentException('Image too large. Maximum size: ' . ($this->maxImageSize / 1024 / 1024) . 'MB');
        }
    }

    protected function validateVideo(UploadedFile $file): void
    {
        $this->validateFile($file);
        
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $this->allowedVideoTypes)) {
            throw new \InvalidArgumentException('Invalid video type. Allowed: ' . implode(', ', $this->allowedVideoTypes));
        }
    }

    protected function validateDocument(UploadedFile $file): void
    {
        $this->validateFile($file);
        
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $this->allowedDocumentTypes)) {
            throw new \InvalidArgumentException('Invalid document type. Allowed: ' . implode(', ', $this->allowedDocumentTypes));
        }
    }

    protected function determineFileType(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        
        if (in_array($extension, $this->allowedImageTypes)) {
            return 'image';
        } elseif (in_array($extension, $this->allowedVideoTypes)) {
            return 'video';
        } elseif (in_array($extension, $this->allowedDocumentTypes)) {
            return 'document';
        }
        
        return 'unknown';
    }

    protected function generateFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $sanitizedName = Str::slug($originalName);
        
        return $sanitizedName . '_' . time() . '_' . Str::random(8) . '.' . $extension;
    }

    protected function createMediaRecord(UploadedFile $file, $model, array $additionalData = []): Media
    {
        $data = array_merge([
            'mediable_type' => get_class($model),
            'mediable_id' => $model->id,
            'filename' => $this->generateFilename($file),
            'original_filename' => $file->getClientOriginalName(),
            'disk' => 'public',
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => auth()->id(),
        ], $additionalData);
        
        return Media::create($data);
    }

    protected function deleteThumbnails(string $imagePath): void
    {
        $pathInfo = pathinfo($imagePath);
        $thumbsDir = $pathInfo['dirname'] . '/thumbs';
        
        try {
            $thumbnailFiles = Storage::disk('public')->files($thumbsDir);
            
            foreach ($thumbnailFiles as $thumbFile) {
                if (str_contains($thumbFile, $pathInfo['filename'])) {
                    Storage::disk('public')->delete($thumbFile);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Thumbnail deletion failed', [
                'image_path' => $imagePath,
                'error' => $e->getMessage()
            ]);
        }
    }
}