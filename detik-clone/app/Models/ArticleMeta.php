<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArticleMeta extends Model
{
    use HasFactory;

    protected $table = 'article_meta';

    protected $fillable = [
        'article_id',
        'meta_key',
        'meta_value',
        'meta_type',
        'is_public',
        'sort_order',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'sort_order' => 'integer',
        'meta_value' => 'json',
    ];

    // ===== RELATIONSHIPS =====

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    // ===== SCOPES =====

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopePrivate($query)
    {
        return $query->where('is_public', false);
    }

    public function scopeByKey($query, string $key)
    {
        return $query->where('meta_key', $key);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('meta_type', $type);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('meta_key');
    }

    // ===== STATIC METHODS =====

    public static function setMeta(Article $article, string $key, mixed $value, string $type = 'string', bool $isPublic = true): self
    {
        return static::updateOrCreate(
            [
                'article_id' => $article->id,
                'meta_key' => $key,
            ],
            [
                'meta_value' => $value,
                'meta_type' => $type,
                'is_public' => $isPublic,
            ]
        );
    }

    public static function getMeta(Article $article, string $key, mixed $default = null): mixed
    {
        $meta = static::where('article_id', $article->id)
            ->where('meta_key', $key)
            ->first();

        return $meta ? $meta->getTypedValue() : $default;
    }

    public static function deleteMeta(Article $article, string $key): bool
    {
        return static::where('article_id', $article->id)
            ->where('meta_key', $key)
            ->delete() > 0;
    }

    public static function getAllMeta(Article $article, bool $publicOnly = false): array
    {
        $query = static::where('article_id', $article->id);
        
        if ($publicOnly) {
            $query->public();
        }

        return $query->ordered()
            ->get()
            ->mapWithKeys(function ($meta) {
                return [$meta->meta_key => $meta->getTypedValue()];
            })
            ->toArray();
    }

    // ===== ACCESSORS & METHODS =====

    public function getTypedValue(): mixed
    {
        return match($this->meta_type) {
            'integer' => (int) $this->meta_value,
            'float' => (float) $this->meta_value,
            'boolean' => (bool) $this->meta_value,
            'array', 'object' => is_string($this->meta_value) ? json_decode($this->meta_value, true) : $this->meta_value,
            'date' => $this->meta_value ? \Carbon\Carbon::parse($this->meta_value) : null,
            default => $this->meta_value,
        };
    }

    public function setTypedValue(mixed $value): void
    {
        $this->meta_type = $this->detectType($value);
        $this->meta_value = $this->formatValue($value);
    }

    private function detectType(mixed $value): string
    {
        return match(true) {
            is_int($value) => 'integer',
            is_float($value) => 'float',
            is_bool($value) => 'boolean',
            is_array($value) || is_object($value) => 'array',
            $value instanceof \Carbon\Carbon => 'date',
            default => 'string',
        };
    }

    private function formatValue(mixed $value): mixed
    {
        return match(true) {
            is_array($value) || is_object($value) => json_encode($value),
            $value instanceof \Carbon\Carbon => $value->toISOString(),
            default => $value,
        };
    }

    // ===== CONVENIENCE METHODS =====

    public function isString(): bool
    {
        return $this->meta_type === 'string';
    }

    public function isInteger(): bool
    {
        return $this->meta_type === 'integer';
    }

    public function isFloat(): bool
    {
        return $this->meta_type === 'float';
    }

    public function isBoolean(): bool
    {
        return $this->meta_type === 'boolean';
    }

    public function isArray(): bool
    {
        return in_array($this->meta_type, ['array', 'object']);
    }

    public function isDate(): bool
    {
        return $this->meta_type === 'date';
    }
}