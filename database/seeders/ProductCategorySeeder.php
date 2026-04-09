<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ProductCategory;
use Illuminate\Support\Str;

class ProductCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Men Fashion',
            'Women Fashion',
            'Electronics',
            'Home & Living',
            'Beauty & Health',
        ];

        foreach ($categories as $key => $cat) {
            ProductCategory::create([
                'name' => $cat,
                'slug' => Str::slug($cat) . '-' . uniqid(),
                'description' => $cat . ' category products',
                'image' => null,
                'sort_order' => $key + 1,
                'is_active' => true,
            ]);
        }
    }
}
