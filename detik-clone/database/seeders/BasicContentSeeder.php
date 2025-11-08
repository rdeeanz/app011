<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Tag;

class BasicContentSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Create basic categories
        $categories = [
            ['name' => 'Politik', 'slug' => 'politik', 'description' => 'Berita dan informasi seputar politik Indonesia'],
            ['name' => 'Ekonomi', 'slug' => 'ekonomi', 'description' => 'Berita ekonomi dan bisnis terkini'],
            ['name' => 'Teknologi', 'slug' => 'teknologi', 'description' => 'Perkembangan teknologi dan inovasi terbaru'],
            ['name' => 'Olahraga', 'slug' => 'olahraga', 'description' => 'Berita olahraga dalam dan luar negeri'],
            ['name' => 'Kesehatan', 'slug' => 'kesehatan', 'description' => 'Informasi kesehatan dan gaya hidup sehat'],
            ['name' => 'Pendidikan', 'slug' => 'pendidikan', 'description' => 'Dunia pendidikan dan pembelajaran'],
            ['name' => 'Hiburan', 'slug' => 'hiburan', 'description' => 'Berita hiburan dan selebriti'],
        ];

        foreach ($categories as $categoryData) {
            Category::firstOrCreate(
                ['slug' => $categoryData['slug']],
                $categoryData
            );
        }

        // Create basic tags
        $tags = [
            ['name' => 'Breaking News', 'slug' => 'breaking-news'],
            ['name' => 'Politik', 'slug' => 'politik'],
            ['name' => 'Ekonomi', 'slug' => 'ekonomi'],
            ['name' => 'Teknologi', 'slug' => 'teknologi'],
            ['name' => 'Olahraga', 'slug' => 'olahraga'],
            ['name' => 'Kesehatan', 'slug' => 'kesehatan'],
            ['name' => 'Pendidikan', 'slug' => 'pendidikan'],
            ['name' => 'Hiburan', 'slug' => 'hiburan'],
            ['name' => 'Trending', 'slug' => 'trending'],
            ['name' => 'Viral', 'slug' => 'viral'],
        ];

        foreach ($tags as $tagData) {
            Tag::firstOrCreate(
                ['slug' => $tagData['slug']],
                $tagData
            );
        }

        $this->command->info('Basic categories and tags created successfully!');
    }
}