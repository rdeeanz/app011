<?php

namespace App\Services;

class FeaturedImageService
{
    /**
     * Get category image mapping
     */
    public static function getCategoryImageMapping(): array
    {
        return [
            'teknologi' => [
                'https://images.unsplash.com/photo-1518709268805-4e9042af2176?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1573164713714-d95e436ab8d6?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1581092795442-32d7b73bc1ad?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1519389950473-47ba0277781c?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1677442136019-21780ecad995?w=800&h=600&fit=crop',
            ],
            'politik' => [
                'https://images.unsplash.com/photo-1529107386315-e1a2ed48a620?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1495435229349-e86db7bfa013?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1551836022-deb4988cc6c0?w=800&h=600&fit=crop',
            ],
            'ekonomi' => [
                'https://images.unsplash.com/photo-1611974789855-9c2a0a7236a3?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1560472354-b33ff0c44a43?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1554224155-6726b3ff858f?w=800&h=600&fit=crop',
            ],
            'olahraga' => [
                'https://images.unsplash.com/photo-1431324155629-1a6deb1dec8d?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1577223625816-7546f13df25d?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1579952363873-27d3bfad9c0d?w=800&h=600&fit=crop',
            ],
            'kesehatan' => [
                'https://images.unsplash.com/photo-1559757148-5c350d0d3c56?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1576091160399-112ba8d25d1f?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1584362917165-526a968579e8?w=800&h=600&fit=crop',
            ],
            'pendidikan' => [
                'https://images.unsplash.com/photo-1513475382585-d06e58bcb0e0?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1481627834876-b7833e8f5570?w=800&h=600&fit=crop',
            ],
            'hiburan' => [
                'https://images.unsplash.com/photo-1489599763733-be7e11b52286?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1518611012118-696072aa579a?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1460881680858-30d872d5b530?w=800&h=600&fit=crop',
            ],
            'default' => [
                'https://images.unsplash.com/photo-1504711434969-e33886168f5c?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1585829365295-ab7cd400c167?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1586776235017-d25c31de90a0?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1495020689067-958852a7765e?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1521295121783-8a321d551ad2?w=800&h=600&fit=crop',
            ]
        ];
    }

    /**
     * Get specific article image mapping
     */
    public static function getSpecificArticleImageMapping(): array
    {
        return [
            'perkembangan-teknologi-ai-di-indonesia-mencapai-titik-terobosan-baru' => 'https://images.unsplash.com/photo-1677442136019-21780ecad995?w=800&h=600&fit=crop',
            'ekonomi-indonesia-menunjukkan-tren-positif-di-kuartal-ketiga-2025' => 'https://images.unsplash.com/photo-1611974789855-9c2a0a7236a3?w=800&h=600&fit=crop',
            'timnas-indonesia-lolos-ke-babak-final-piala-aff-2025' => 'https://images.unsplash.com/photo-1431324155629-1a6deb1dec8d?w=800&h=600&fit=crop',
            'peluncuran-satelit-nusantara-5-memperkuat-konektivitas-internet-indonesia' => 'https://images.unsplash.com/photo-1446776877081-d282a0f896e2?w=800&h=600&fit=crop',
            'kebijakan-baru-pajak-digital-mulai-berlaku-1-januari-2026' => 'https://images.unsplash.com/photo-1551836022-deb4988cc6c0?w=800&h=600&fit=crop',
            'startup-edtech-indonesia-raih-pendanaan-seri-b-senilai-50-juta' => 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=800&h=600&fit=crop',
            'indonesia-terpilih-menjadi-tuan-rumah-ktt-g20-tahun-2027' => 'https://images.unsplash.com/photo-1529107386315-e1a2ed48a620?w=800&h=600&fit=crop',
            'inovasi-energi-terbarukan-panel-surya-terapung-terbesar-di-asia-tenggara' => 'https://images.unsplash.com/photo-1559302504-64c8ddb8ddf8?w=800&h=600&fit=crop',
            'liga-1-indonesia-musim-2025-2026-persija-jakarta-memimpin-klasemen' => 'https://images.unsplash.com/photo-1577223625816-7546f13df25d?w=800&h=600&fit=crop',
            'terobosan-medis-uji-klinis-vaksin-dengue-buatan-indonesia-fase-iii-berhasil' => 'https://images.unsplash.com/photo-1559757148-5c350d0d3c56?w=800&h=600&fit=crop',
        ];
    }

    /**
     * Get featured image based on article content or category
     */
    public static function getImageForArticle(string $title, ?string $categorySlug = null): string
    {
        // Map categories to curated image collections
        $categoryImages = self::getCategoryImageMapping();

        // Default fallback images
        $defaultImages = $categoryImages['default'];

        // Smart matching based on title keywords
        $titleLower = strtolower($title);
        
        if (strpos($titleLower, 'teknologi') !== false || strpos($titleLower, 'ai') !== false || strpos($titleLower, 'digital') !== false) {
            $images = $categoryImages['teknologi'];
        } elseif (strpos($titleLower, 'ekonomi') !== false || strpos($titleLower, 'bisnis') !== false || strpos($titleLower, 'keuangan') !== false) {
            $images = $categoryImages['ekonomi'];
        } elseif (strpos($titleLower, 'olahraga') !== false || strpos($titleLower, 'sepak bola') !== false || strpos($titleLower, 'timnas') !== false) {
            $images = $categoryImages['olahraga'];
        } elseif (strpos($titleLower, 'politik') !== false || strpos($titleLower, 'pemerintah') !== false || strpos($titleLower, 'kebijakan') !== false) {
            $images = $categoryImages['politik'];
        } elseif (strpos($titleLower, 'kesehatan') !== false || strpos($titleLower, 'medis') !== false || strpos($titleLower, 'vaksin') !== false) {
            $images = $categoryImages['kesehatan'];
        } elseif (strpos($titleLower, 'pendidikan') !== false || strpos($titleLower, 'sekolah') !== false || strpos($titleLower, 'edtech') !== false) {
            $images = $categoryImages['pendidikan'];
        } elseif ($categorySlug && isset($categoryImages[$categorySlug])) {
            $images = $categoryImages[$categorySlug];
        } else {
            $images = $defaultImages;
        }

        return $images[array_rand($images)];
    }

    /**
     * Get image dimensions from URL
     */
    public static function getImageDimensions(string $url): array
    {
        // For Unsplash URLs, we can extract dimensions from the URL parameters
        if (strpos($url, 'unsplash.com') !== false) {
            preg_match('/[?&]w=(\d+)/', $url, $widthMatch);
            preg_match('/[?&]h=(\d+)/', $url, $heightMatch);
            
            return [
                'width' => $widthMatch[1] ?? 800,
                'height' => $heightMatch[1] ?? 600,
            ];
        }

        return ['width' => 800, 'height' => 600];
    }

    /**
     * Generate responsive image URLs for different screen sizes
     */
    public static function getResponsiveImages(string $baseUrl): array
    {
        if (strpos($baseUrl, 'unsplash.com') !== false) {
            $baseUrl = preg_replace('/[?&]w=\d+/', '', $baseUrl);
            $baseUrl = preg_replace('/[?&]h=\d+/', '', $baseUrl);
            $baseUrl .= (strpos($baseUrl, '?') !== false) ? '&' : '?';
            
            return [
                'thumbnail' => $baseUrl . 'w=150&h=150&fit=crop',
                'small' => $baseUrl . 'w=300&h=200&fit=crop',
                'medium' => $baseUrl . 'w=600&h=400&fit=crop',
                'large' => $baseUrl . 'w=800&h=600&fit=crop',
                'xl' => $baseUrl . 'w=1200&h=800&fit=crop',
            ];
        }

        return [
            'thumbnail' => $baseUrl,
            'small' => $baseUrl,
            'medium' => $baseUrl,
            'large' => $baseUrl,
            'xl' => $baseUrl,
        ];
    }
}