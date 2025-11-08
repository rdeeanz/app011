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
}
