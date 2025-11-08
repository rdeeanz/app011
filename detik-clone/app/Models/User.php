<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'avatar',
        'bio',
        'phone',
        'social_links',
        'is_active',
        'last_login_at',
        'preferences',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'social_links' => 'array',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'preferences' => 'array',
        ];
    }

    protected $attributes = [
        'role' => 'subscriber',
        'is_active' => true,
    ];

    // Relationships
    public function articles()
    {
        return $this->hasMany(Article::class, 'author_id');
    }

    public function editedArticles()
    {
        return $this->hasMany(Article::class, 'editor_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function approvedComments()
    {
        return $this->hasMany(Comment::class, 'approved_by');
    }

    public function bookmarks()
    {
        return $this->hasMany(Bookmark::class);
    }

    // Role methods
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isEditor(): bool
    {
        return $this->role === 'editor';
    }

    public function isAuthor(): bool
    {
        return $this->role === 'author';
    }

    public function canPublish(): bool
    {
        return in_array($this->role, ['admin', 'editor']);
    }

    public function canModerate(): bool
    {
        return in_array($this->role, ['admin', 'editor']);
    }

    // ===== PII & GDPR COMPLIANCE METHODS =====

    /**
     * Anonymize user data for GDPR compliance
     */
    public function anonymize(): bool
    {
        return $this->update([
            'name' => 'Deleted User',
            'email' => 'deleted_user_' . $this->id . '@deleted.local',
            'phone' => null,
            'avatar' => null,
            'bio' => null,
            'social_links' => null,
            'preferences' => null,
            'is_active' => false,
        ]);
    }

    /**
     * Export user data for GDPR data portability
     */
    public function exportPersonalData(): array
    {
        return [
            'profile' => [
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'bio' => $this->bio,
                'social_links' => $this->social_links,
                'created_at' => $this->created_at,
                'last_login_at' => $this->last_login_at,
            ],
            'articles' => $this->articles()->pluck('title', 'slug')->toArray(),
            'comments' => $this->comments()->with('article:id,title,slug')->get()->map(function ($comment) {
                return [
                    'content' => $comment->content,
                    'article' => $comment->article ? $comment->article->title : 'Deleted Article',
                    'article_slug' => $comment->article ? $comment->article->slug : null,
                    'created_at' => $comment->created_at,
                ];
            })->toArray(),
            'bookmarks' => $this->bookmarks()->with('article:id,title,slug')->get()->map(function ($bookmark) {
                return [
                    'article' => $bookmark->article ? $bookmark->article->title : 'Deleted Article',
                    'article_slug' => $bookmark->article ? $bookmark->article->slug : null,
                    'bookmarked_at' => $bookmark->created_at,
                ];
            })->toArray(),
        ];
    }

    /**
     * Check if user has given consent for data processing
     */
    public function hasConsentFor(string $purpose): bool
    {
        $consents = $this->preferences['data_consents'] ?? [];
        return $consents[$purpose] ?? false;
    }

    /**
     * Update consent for data processing
     */
    public function updateConsent(string $purpose, bool $granted): bool
    {
        $preferences = $this->preferences ?? [];
        $preferences['data_consents'][$purpose] = $granted;
        $preferences['consent_updated_at'] = now()->toISOString();
        
        return $this->update(['preferences' => $preferences]);
    }
}
