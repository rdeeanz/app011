<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use App\Models\Tag;

class DummyArticleSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Get existing users, categories, and tags
        $users = User::where('role', 'admin')->orWhere('role', 'author')->get();
        $categories = Category::all();
        $tags = Tag::all();

        if ($users->isEmpty()) {
            $this->command->error('No admin or author users found. Please create users first.');
            return;
        }

        if ($categories->isEmpty()) {
            $this->command->error('No categories found. Please create categories first.');
            return;
        }

        // Create sample tags if none exist
        if ($tags->isEmpty()) {
            $tags = collect([
                Tag::create(['name' => 'Breaking News', 'slug' => 'breaking-news']),
                Tag::create(['name' => 'Politik', 'slug' => 'politik']),
                Tag::create(['name' => 'Ekonomi', 'slug' => 'ekonomi']),
                Tag::create(['name' => 'Teknologi', 'slug' => 'teknologi']),
                Tag::create(['name' => 'Olahraga', 'slug' => 'olahraga']),
            ]);
        }

        $dummyArticles = [
            [
                'title' => 'Perkembangan Teknologi AI di Indonesia Mencapai Titik Terobosan Baru',
                'content' => $this->generateContent('Teknologi kecerdasan buatan (AI) di Indonesia mengalami kemajuan pesat dalam beberapa bulan terakhir. Berbagai startup lokal mulai mengembangkan solusi AI yang dapat diterapkan dalam berbagai sektor, mulai dari kesehatan, pendidikan, hingga pertanian. Pemerintah juga memberikan dukungan penuh melalui program digitalisasi nasional yang telah dicanangkan sejak awal tahun ini.'),
                'excerpt' => 'Teknologi AI di Indonesia berkembang pesat dengan dukungan pemerintah dan inovasi startup lokal di berbagai sektor.',
                'category' => 'Teknologi',
                'tags' => ['Teknologi', 'Breaking News'],
                'featured_image' => 'https://via.placeholder.com/800x600/0066cc/ffffff?text=AI+Technology'
            ],
            [
                'title' => 'Ekonomi Indonesia Menunjukkan Tren Positif di Kuartal Ketiga 2025',
                'content' => $this->generateContent('Badan Pusat Statistik (BPS) melaporkan bahwa pertumbuhan ekonomi Indonesia di kuartal ketiga 2025 mencapai 5.8%, melampaui target pemerintah sebesar 5.5%. Sektor yang paling berkontribusi adalah industri manufaktur, teknologi informasi, dan pariwisata. Inflasi juga terkendali di level 2.3%, menunjukkan stabilitas ekonomi yang baik.'),
                'excerpt' => 'Pertumbuhan ekonomi Indonesia mencapai 5.8% di kuartal ketiga 2025, melampaui target pemerintah.',
                'category' => 'Ekonomi',
                'tags' => ['Ekonomi', 'Politik'],
                'featured_image' => 'https://via.placeholder.com/800x600/00aa44/ffffff?text=Economic+Growth'
            ],
            [
                'title' => 'Timnas Indonesia Lolos ke Babak Final Piala AFF 2025',
                'content' => $this->generateContent('Timnas Indonesia berhasil mengalahkan Thailand dengan skor 2-1 di semifinal Piala AFF 2025 yang berlangsung di Stadion Gelora Bung Karno. Dua gol kemenangan dicetak oleh Egy Maulana Vikri di menit ke-23 dan Witan Sulaeman di menit ke-67. Indonesia akan menghadapi Vietnam di partai final yang dijadwalkan pada 15 November 2025.'),
                'excerpt' => 'Timnas Indonesia mengalahkan Thailand 2-1 dan melaju ke final Piala AFF 2025.',
                'category' => 'Olahraga',
                'tags' => ['Olahraga', 'Breaking News'],
                'featured_image' => 'https://via.placeholder.com/800x600/cc0000/ffffff?text=Football+Victory'
            ],
            [
                'title' => 'Peluncuran Satelit Nusantara-5 Memperkuat Konektivitas Internet Indonesia',
                'content' => $this->generateContent('Satelit Nusantara-5 berhasil diluncurkan dari Kennedy Space Center, Florida, dan akan memperkuat jaringan internet di seluruh Indonesia, terutama di daerah terpencil. Satelit ini memiliki kapasitas 50 Gbps dan dapat melayani hingga 10 juta pengguna secara bersamaan. Proyek ini merupakan bagian dari program Indonesia Digital 2030.'),
                'excerpt' => 'Satelit Nusantara-5 diluncurkan untuk memperkuat konektivitas internet di seluruh Indonesia.',
                'category' => 'Teknologi',
                'tags' => ['Teknologi'],
                'featured_image' => 'https://via.placeholder.com/800x600/663399/ffffff?text=Satellite+Launch'
            ],
            [
                'title' => 'Kebijakan Baru Pajak Digital Mulai Berlaku 1 Januari 2026',
                'content' => $this->generateContent('Pemerintah Indonesia mengumumkan kebijakan pajak digital baru yang akan mulai berlaku pada 1 Januari 2026. Kebijakan ini akan mengenakan pajak 11% untuk semua transaksi digital, termasuk e-commerce, aplikasi transportasi online, dan layanan streaming. Pemerintah memperkirakan kebijakan ini akan meningkatkan pendapatan negara hingga Rp 15 triliun per tahun.'),
                'excerpt' => 'Kebijakan pajak digital 11% akan berlaku mulai 1 Januari 2026 untuk semua transaksi digital.',
                'category' => 'Politik',
                'tags' => ['Politik', 'Ekonomi'],
                'featured_image' => 'https://via.placeholder.com/800x600/ff6600/ffffff?text=Digital+Tax'
            ],
            [
                'title' => 'Startup EdTech Indonesia Raih Pendanaan Seri B Senilai $50 Juta',
                'content' => $this->generateContent('Ruangguru, platform edukasi online terkemuka Indonesia, berhasil meraih pendanaan Seri B senilai $50 juta USD dari investor internasional. Dana ini akan digunakan untuk ekspansi ke negara-negara ASEAN lainnya dan pengembangan teknologi AI untuk personalisasi pembelajaran. Ruangguru kini telah melayani lebih dari 22 juta pengguna di Indonesia.'),
                'excerpt' => 'Ruangguru meraih pendanaan $50 juta untuk ekspansi regional dan pengembangan AI.',
                'category' => 'Teknologi',
                'tags' => ['Teknologi', 'Ekonomi'],
                'featured_image' => 'https://via.placeholder.com/800x600/00ccaa/ffffff?text=EdTech+Funding'
            ],
            [
                'title' => 'Indonesia Terpilih Menjadi Tuan Rumah KTT G20 Tahun 2027',
                'content' => $this->generateContent('Indonesia secara resmi terpilih menjadi tuan rumah Konferensi Tingkat Tinggi (KTT) G20 tahun 2027. Keputusan ini diambil dalam pertemuan G20 di Brasil dengan suara bulat dari 19 negara anggota. Indonesia berencana mengadakan KTT di Bali dengan tema "Sustainable Growth for Global Prosperity". Persiapan sudah dimulai dengan pembentukan komite nasional.'),
                'excerpt' => 'Indonesia terpilih sebagai tuan rumah KTT G20 2027 yang akan diselenggarakan di Bali.',
                'category' => 'Politik',
                'tags' => ['Politik', 'Breaking News'],
                'featured_image' => 'https://via.placeholder.com/800x600/4d79a4/ffffff?text=G20+Summit'
            ],
            [
                'title' => 'Inovasi Energi Terbarukan: Panel Surya Terapung Terbesar di Asia Tenggara',
                'content' => $this->generateContent('Pembangkit Listrik Tenaga Surya (PLTS) terapung terbesar di Asia Tenggara telah beroperasi di Waduk Cirata, Jawa Barat. Dengan kapasitas 145 MW, fasilitas ini dapat memasok listrik untuk 50,000 rumah tangga. Proyek senilai $145 juta ini merupakan hasil kerjasama antara PLN dan investor Uni Emirat Arab, menandai komitmen Indonesia terhadap energi bersih.'),
                'excerpt' => 'PLTS terapung terbesar di Asia Tenggara beroperasi di Waduk Cirata dengan kapasitas 145 MW.',
                'category' => 'Teknologi',
                'tags' => ['Teknologi'],
                'featured_image' => 'https://via.placeholder.com/800x600/339966/ffffff?text=Solar+Power'
            ],
            [
                'title' => 'Liga 1 Indonesia Musim 2025/2026: Persija Jakarta Memimpin Klasemen',
                'content' => $this->generateContent('Persija Jakarta berhasil mempertahankan posisi puncak klasemen Liga 1 Indonesia setelah mengalahkan Bali United 3-1 di Stadion Gelora Bung Karno. Dengan koleksi 28 poin dari 12 pertandingan, Persija unggul 5 poin dari Persib Bandung di posisi kedua. Pelatih Persija, Sergio Farias, memuji konsistensi tim dalam menjalankan strategi permainan.'),
                'excerpt' => 'Persija Jakarta memimpin klasemen Liga 1 dengan 28 poin setelah mengalahkan Bali United 3-1.',
                'category' => 'Olahraga',
                'tags' => ['Olahraga'],
                'featured_image' => 'https://via.placeholder.com/800x600/cc3333/ffffff?text=Liga+1'
            ],
            [
                'title' => 'Terobosan Medis: Uji Klinis Vaksin Dengue Buatan Indonesia Fase III Berhasil',
                'content' => $this->generateContent('Bio Farma Indonesia mengumumkan keberhasilan uji klinis fase III untuk vaksin dengue yang dikembangkan secara lokal. Vaksin bernama "DengVax-Indo" menunjukkan efektivitas 89% dalam mencegah demam berdarah dengue. Uji klinis melibatkan 30,000 sukarelawan di 15 kota besar Indonesia. Vaksin direncanakan akan tersedia untuk masyarakat umum pada pertengahan 2026.'),
                'excerpt' => 'Uji klinis fase III vaksin dengue buatan Indonesia berhasil dengan efektivitas 89%.',
                'category' => 'Kesehatan',
                'tags' => ['Breaking News'],
                'featured_image' => 'https://via.placeholder.com/800x600/6699cc/ffffff?text=Medical+Breakthrough'
            ]
        ];

        foreach ($dummyArticles as $index => $articleData) {
            // Find or create category
            $category = $categories->where('name', $articleData['category'])->first() 
                       ?? $categories->first();
            
            // Create article
            $article = Article::create([
                'title' => $articleData['title'],
                'slug' => \Str::slug($articleData['title']),
                'content' => $articleData['content'],
                'excerpt' => $articleData['excerpt'],
                'status' => 'published',
                'published_at' => now()->subDays(rand(0, 30)),
                'author_id' => $users->random()->id,
                'category_id' => $category->id,
                'featured_image' => $articleData['featured_image'],
                'meta_title' => $articleData['title'],
                'meta_description' => $articleData['excerpt'],
                'views_count' => rand(100, 5000),
                'likes_count' => rand(10, 500),
                'comments_count' => rand(0, 50),
                'is_featured' => $index < 3, // First 3 articles are featured
                'allow_comments' => true,
                'seo_score' => rand(70, 95),
            ]);

            // Attach tags
            foreach ($articleData['tags'] as $tagName) {
                $tag = $tags->where('name', $tagName)->first();
                if ($tag) {
                    $article->tags()->attach($tag->id);
                }
            }

            $this->command->info("Created article: {$article->title}");
        }

        $this->command->info('Successfully created 10 dummy articles!');
    }

    /**
     * Generate rich content for articles
     */
    private function generateContent(string $intro): string
    {
        $paragraphs = [
            $intro,
            "Menurut para ahli, perkembangan ini menandai era baru dalam transformasi digital Indonesia. Berbagai sektor industri mulai merasakan dampak positif dari inovasi teknologi yang terus berkembang pesat.",
            "Data terbaru menunjukkan peningkatan signifikan dalam adopsi teknologi oleh masyarakat Indonesia. Hal ini didukung oleh infrastruktur digital yang semakin memadai dan program literasi digital yang gencar dilakukan pemerintah.",
            "Ke depannya, diharapkan tren positif ini dapat terus berlanjut dan memberikan manfaat yang lebih luas bagi seluruh masyarakat Indonesia. Kolaborasi antara pemerintah, swasta, dan akademisi menjadi kunci utama dalam mewujudkan visi Indonesia digital.",
            "Para stakeholder optimis bahwa momentum ini akan membawa Indonesia menjadi salah satu kekuatan digital terdepan di Asia Tenggara dalam beberapa tahun ke depan."
        ];

        return implode("\n\n", $paragraphs);
    }
}