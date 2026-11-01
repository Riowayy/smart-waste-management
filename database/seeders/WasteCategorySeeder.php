<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WasteCategory;

class WasteCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Organic Waste',
                'slug' => 'organic-waste',
                'description' => 'Sampah yang berasal dari bahan organik dan mudah terurai.',
            ],
            [
                'name' => 'Plastic Waste',
                'slug' => 'plastic-waste',
                'description' => 'Sampah yang berasal dari bahan plastik.',
            ],
            [
                'name' => 'Paper Waste',
                'slug' => 'paper-waste',
                'description' => 'Sampah yang berasal dari kertas dan produk turunannya.',
            ],
        ];

        foreach ($categories as $category) {
            WasteCategory::updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}