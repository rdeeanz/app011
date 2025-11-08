<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Article;
use App\Services\FeaturedImageService;

class ArticleFeaturedImageSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Get image mappings from the central service
        $imageMapping = FeaturedImageService::getCategoryImageMapping();
        $specificArticleImages = FeaturedImageService::getSpecificArticleImageMapping();

        $articles = Article::all();
        $updatedCount = 0;

        foreach ($articles as $article) {
            $image = null;

            // Try to get specific image for this article
            if (isset($specificArticleImages[$article->slug])) {
                $image = $specificArticleImages[$article->slug];
            }
            // If no specific image, try to match by category
            elseif ($article->category) {
                $categorySlug = strtolower($article->category->slug);
                if (isset($imageMapping[$categorySlug])) {
                    $images = $imageMapping[$categorySlug];
                    $image = $images[array_rand($images)];
                }
            }
            
            // Fallback to default images
            if (!$image) {
                $defaultImages = $imageMapping['default'];
                $image = $defaultImages[array_rand($defaultImages)];
            }

            // Update the article with the featured image
            if (!$article->featured_image || strpos($article->featured_image, 'placeholder') !== false) {
                $article->update(['featured_image' => $image]);
                $updatedCount++;
                $this->command->info("Updated featured image for: {$article->title}");
            }
        }

        $this->command->info("Successfully updated {$updatedCount} articles with featured images!");
    }
}