<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ProductSubCategory;
use App\Models\ProductCategory;
use Illuminate\Support\Str;

class ProductSubCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            'Men Fashion' => ['Shirts', 'T-Shirts', 'Pants', 'Shoes'],
            'Women Fashion' => ['Dresses', 'Tops', 'Handbags', 'Shoes'],
            'Electronics' => ['Mobile Phones', 'Laptops', 'Accessories'],
            'Home & Living' => ['Furniture', 'Kitchen', 'Decor'],
            'Beauty & Health' => ['Skincare', 'Makeup', 'Hair Care'],
        ];

        foreach ($data as $categoryName => $subcategories) {
            $category = ProductCategory::where('name', $categoryName)->first();

            if (!$category) continue;

            foreach ($subcategories as $key => $sub) {
                ProductSubCategory::create([
                    'category_id' => $category->id,
                    'name' => $sub,
                    'slug' => Str::slug($sub) . '-' . uniqid(),
                    'description' => $sub . ' under ' . $categoryName,
                    'image' => null,
                    'sort_order' => $key + 1,
                    'is_active' => true,
                ]);
            }
        }
    }
}
