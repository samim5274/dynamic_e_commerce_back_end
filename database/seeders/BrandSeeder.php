<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Brand;
use Illuminate\Support\Str;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brands = [
            ['name' => 'Nike'],
            ['name' => 'Adidas'],
            ['name' => 'Puma'],
            ['name' => 'Zara'],
            ['name' => 'H&M'],
            ['name' => 'Gucci'],
            ['name' => 'Louis Vuitton'],
        ];

        foreach ($brands as $key => $brand) {
            Brand::create([
                'name' => $brand['name'],
                'slug' => Str::slug($brand['name']) . '-' . uniqid(),
                'description' => $brand['name'] . ' premium brand products',
                'image' => null,
                'sort_order' => $key + 1,
                'is_active' => true,
            ]);
        }
    }
}
