<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'News',
                'description' => 'Berita terkini dalam dan luar negeri',
                'color' => '#C4171D',
                'icon' => 'newspaper',
                'sort_order' => 1,
            ],
            [
                'name' => 'Finance',
                'description' => 'Berita ekonomi dan keuangan',
                'color' => '#10B981',
                'icon' => 'currency-dollar',
                'sort_order' => 2,
            ],
            [
                'name' => 'Sports',
                'description' => 'Berita olahraga',
                'color' => '#F59E0B',
                'icon' => 'trophy',
                'sort_order' => 3,
            ],
            [
                'name' => 'Technology',
                'description' => 'Berita teknologi dan gadget',
                'color' => '#3B82F6',
                'icon' => 'computer-desktop',
                'sort_order' => 4,
            ],
            [
                'name' => 'Lifestyle',
                'description' => 'Gaya hidup, kesehatan, dan kuliner',
                'color' => '#EC4899',
                'icon' => 'heart',
                'sort_order' => 5,
            ],
            [
                'name' => 'Entertainment',
                'description' => 'Hiburan dan selebriti',
                'color' => '#8B5CF6',
                'icon' => 'film',
                'sort_order' => 6,
            ],
        ];

        foreach ($categories as $categoryData) {
            Category::create($categoryData);
        }

        // Create subcategories
        $newsCategory = Category::where('name', 'News')->first();
        if ($newsCategory) {
            $subCategories = [
                [
                    'name' => 'Politik',
                    'parent_id' => $newsCategory->id,
                    'description' => 'Berita politik Indonesia',
                    'color' => '#DC2626',
                    'sort_order' => 1,
                ],
                [
                    'name' => 'Hukum',
                    'parent_id' => $newsCategory->id,
                    'description' => 'Berita hukum dan kriminal',
                    'color' => '#7C2D12',
                    'sort_order' => 2,
                ],
                [
                    'name' => 'Internasional',
                    'parent_id' => $newsCategory->id,
                    'description' => 'Berita internasional',
                    'color' => '#1E40AF',
                    'sort_order' => 3,
                ],
            ];

            foreach ($subCategories as $subCategoryData) {
                Category::create($subCategoryData);
            }
        }
    }
}
