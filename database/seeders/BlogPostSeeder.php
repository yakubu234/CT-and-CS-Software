<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Database\Seeder;

class BlogPostSeeder extends Seeder
{
    public function run(): void
    {
        $authorId = User::query()->value('id');

        $posts = [
            [
                'title' => 'Giving Hope, Sharing Love: Widows & Elderly Outreach 2026',
                'slug' => 'giving-hope-sharing-love-widows-elderly-outreach-2026',
                'excerpt' => 'Oreoluwapo Ilaro Cooperative continued its compassion-driven outreach by supporting widows and elderly members of the community.',
                'content' => "Our widows and elderly outreach initiative reflects the cooperative spirit at the heart of Oreoluwapo Ilaro.\n\nThrough this programme, the union provided support materials and practical assistance to vulnerable community members, reminding them that they are seen, valued, and remembered.\n\nWe believe cooperative impact should go beyond finance and extend to meaningful acts of care that strengthen the wider community.",
                'featured_image' => 'frontend/images/blogs/blog_six.jpg',
                'status' => true,
                'published_at' => '2026-04-04 09:00:00',
            ],
            [
                'title' => 'The Power of Investment in Cooperatives: Strength in Unity',
                'slug' => 'the-power-of-investment-in-cooperatives-strength-in-unity',
                'excerpt' => 'When members invest together through a trusted cooperative structure, they multiply opportunity and build stronger long-term outcomes.',
                'content' => "Cooperative investment creates a platform where many small efforts become one strong collective force.\n\nAt Oreoluwapo Ilaro, this principle helps members access growth opportunities, improve savings culture, and strengthen financial discipline through accountable community systems.\n\nThe result is more than capital formation. It is shared confidence, shared resilience, and shared progress.",
                'featured_image' => 'frontend/images/blogs/IMG_1852.jpeg',
                'status' => true,
                'published_at' => '2025-02-10 09:00:00',
            ],
            [
                'title' => 'Cooperative Investments: Empowering Members for a Brighter Future',
                'slug' => 'cooperative-investments-empowering-members-for-a-brighter-future',
                'excerpt' => 'Oreoluwapo Ilaro continues to champion investment structures that help members grow with confidence and stability.',
                'content' => "A strong cooperative makes room for members to invest in ways that align with their realities and future goals.\n\nBy promoting responsible contributions, transparent leadership, and collective support, Oreoluwapo Ilaro helps members build brighter futures through practical and sustainable investment channels.\n\nEvery contribution becomes part of a larger story of empowerment and community transformation.",
                'featured_image' => 'frontend/images/blogs/IMG_1853.jpeg',
                'status' => true,
                'published_at' => '2025-01-20 09:00:00',
            ],
            [
                'title' => 'Supporting Agricultural Produce - Empowering Farmers in Cooperatives',
                'slug' => 'supporting-agricultural-produce-empowering-farmers-in-cooperatives',
                'excerpt' => 'Agricultural support remains one of the practical ways cooperative systems can improve livelihoods and strengthen local economies.',
                'content' => "Farmers thrive better when they are supported by structures that understand local production realities.\n\nThrough the cooperative framework, members engaged in agriculture can benefit from shared support, improved planning, and stronger market confidence.\n\nOreoluwapo Ilaro remains committed to cooperative pathways that help farmers produce, grow, and prosper more sustainably.",
                'featured_image' => 'frontend/images/blogs/agro-farmer.jpeg',
                'status' => true,
                'published_at' => '2024-12-23 09:00:00',
            ],
        ];

        foreach ($posts as $post) {
            BlogPost::query()->updateOrCreate(
                ['slug' => $post['slug']],
                [
                    ...$post,
                    'created_by' => $authorId,
                    'updated_by' => $authorId,
                ]
            );
        }
    }
}
