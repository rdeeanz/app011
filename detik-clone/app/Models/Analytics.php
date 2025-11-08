<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Carbon\Carbon;

class Analytics extends Model
{
    use HasFactory;

    protected $fillable = [
        'trackable_type',
        'trackable_id',
        'event_type',
        'event_data',
        'user_id',
        'session_id',
        'ip_address',
        'user_agent',
        'referrer',
        'country',
        'city',
        'device_type',
        'browser',
        'platform',
        'occurred_at',
    ];

    protected $casts = [
        'event_data' => 'array',
        'occurred_at' => 'datetime',
    ];

    // ===== RELATIONSHIPS =====

    public function trackable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ===== SCOPES =====

    public function scopeViews($query)
    {
        return $query->where('event_type', 'view');
    }

    public function scopeShares($query)
    {
        return $query->where('event_type', 'share');
    }

    public function scopeComments($query)
    {
        return $query->where('event_type', 'comment');
    }

    public function scopeBookmarks($query)
    {
        return $query->where('event_type', 'bookmark');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('occurred_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('occurred_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('occurred_at', now()->month)
                    ->whereYear('occurred_at', now()->year);
    }

    public function scopeDateRange($query, Carbon $from, Carbon $to)
    {
        return $query->whereBetween('occurred_at', [$from, $to]);
    }

    public function scopeByCountry($query, string $country)
    {
        return $query->where('country', $country);
    }

    public function scopeByDevice($query, string $deviceType)
    {
        return $query->where('device_type', $deviceType);
    }

    public function scopeByBrowser($query, string $browser)
    {
        return $query->where('browser', $browser);
    }

    // ===== STATIC METHODS =====

    /**
     * Anonymize IP address for privacy compliance
     */
    public static function anonymizeIp(string $ip): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            // IPv4: mask last octet
            return preg_replace('/\.\d+$/', '.0', $ip);
        } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            // IPv6: mask last 64 bits
            $parts = explode(':', $ip);
            return implode(':', array_slice($parts, 0, 4)) . '::';
        }
        
        return '0.0.0.0'; // fallback for invalid IPs
    }

    public static function track(
        Model $trackable,
        string $eventType,
        array $eventData = [],
        ?User $user = null
    ): self {
        $request = request();
        
        // Only collect PII if user has consented (check session or cookie)
        $hasConsent = $request->session()->get('analytics_consent', false) || 
                     $request->cookie('analytics_consent') === 'true';
        
        $analyticsData = [
            'trackable_type' => get_class($trackable),
            'trackable_id' => $trackable->id,
            'event_type' => $eventType,
            'event_data' => $eventData,
            'user_id' => $user?->id ?? auth()->id(),
            'session_id' => $hasConsent ? $request->session()->getId() : hash('sha256', $request->session()->getId()),
            'ip_address' => static::anonymizeIp($request->ip()),
            'user_agent' => $hasConsent ? $request->userAgent() : hash('sha256', $request->userAgent() . config('analytics.salt')),
            'referrer' => $hasConsent ? $request->headers->get('referer') : null,
            'device_type' => static::detectDeviceType($request->userAgent()),
            'browser' => static::detectBrowser($request->userAgent()),
            'platform' => static::detectPlatform($request->userAgent()),
            'occurred_at' => now(),
        ];
        
        return static::create($analyticsData);
    }

    public static function getPopularContent(string $type = null, int $days = 7, int $limit = 10)
    {
        $query = static::views()
            ->where('occurred_at', '>=', now()->subDays($days))
            ->selectRaw('trackable_type, trackable_id, COUNT(*) as view_count')
            ->groupBy('trackable_type', 'trackable_id')
            ->orderBy('view_count', 'desc')
            ->limit($limit);

        if ($type) {
            $query->where('trackable_type', $type);
        }

        return $query->get();
    }

    public static function getUserEngagement(User $user, int $days = 30): array
    {
        $analytics = static::where('user_id', $user->id)
            ->where('occurred_at', '>=', now()->subDays($days))
            ->selectRaw('event_type, COUNT(*) as count')
            ->groupBy('event_type')
            ->pluck('count', 'event_type')
            ->toArray();

        return [
            'views' => $analytics['view'] ?? 0,
            'shares' => $analytics['share'] ?? 0,
            'comments' => $analytics['comment'] ?? 0,
            'bookmarks' => $analytics['bookmark'] ?? 0,
            'total_interactions' => array_sum($analytics),
        ];
    }

    // ===== HELPER METHODS =====

    private static function detectDeviceType(?string $userAgent): string
    {
        if (!$userAgent) return 'unknown';

        if (preg_match('/mobile|android|iphone|ipod|blackberry|iemobile/i', $userAgent)) {
            return 'mobile';
        }

        if (preg_match('/tablet|ipad|playbook|silk/i', $userAgent)) {
            return 'tablet';
        }

        return 'desktop';
    }

    private static function detectBrowser(?string $userAgent): string
    {
        if (!$userAgent) return 'unknown';

        $browsers = [
            'Chrome' => '/Chrome\/[\d.]+/i',
            'Firefox' => '/Firefox\/[\d.]+/i',
            'Safari' => '/Safari\/[\d.]+/i',
            'Edge' => '/Edge\/[\d.]+/i',
            'Internet Explorer' => '/MSIE [\d.]+/i',
            'Opera' => '/Opera\/[\d.]+/i',
        ];

        foreach ($browsers as $browser => $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return $browser;
            }
        }

        return 'unknown';
    }

    private static function detectPlatform(?string $userAgent): string
    {
        if (!$userAgent) return 'unknown';

        $platforms = [
            'Windows' => '/Windows/i',
            'Mac' => '/Macintosh|Mac OS X/i',
            'Linux' => '/Linux/i',
            'iOS' => '/iPhone|iPad|iPod/i',
            'Android' => '/Android/i',
        ];

        foreach ($platforms as $platform => $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return $platform;
            }
        }

        return 'unknown';
    }
}