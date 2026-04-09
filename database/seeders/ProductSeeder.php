<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductImage;
use App\Models\ProductCategory;
use App\Models\ProductSubCategory;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some random categories, subcategories, brands, and a vendor
        $categories = ProductCategory::all();
        $subcategories = ProductSubCategory::all();
        $brands = Brand::all();

        if(!$categories->count() || !$subcategories->count() || !$brands->count()){
            $this->command->error('Make sure categories, subcategories, brands and vendors exist.');
            return;
        }

        for ($i = 1; $i <= 500; $i++) {
            $category = $categories->random();
            $subcategory = $subcategories->where('category_id', $category->id)->random();
            $brand = $brands->random();

            $name = "Sample Product {$i}";
            $slug = Str::slug($name);

            $product = Product::create([
                'name'             => $name,
                'slug'             => $slug,
                'sku'              => 'SKU-' . Str::upper(Str::random(6)),
                'category_id'      => $category->id,
                'subcategory_id'   => $subcategory->id,
                'brand_id'         => $brand->id,
                'price'            => rand(100, 2000),
                'discount_price'   => rand(50, 1000),
                'stock_quantity'   => rand(5, 50),
                'min_stock'        => 5,
                'summary'          => "This is a summary of {$name}",
                'description'      => "Detailed description of {$name}",
                'meta_title'       => $name,
                'meta_keywords'    => "sample, product, {$i}",
                'meta_description' => "Meta description for {$name}",
                'is_featured'      => rand(0,1),
                'is_on_sale'       => rand(0,1),
                'is_active'        => 1,
            ]);

            // Add 1-3 variants randomly
            $variantCount = rand(1,3);
            for($v=0; $v<$variantCount; $v++){
                ProductVariant::create([
                    'product_id'        => $product->id,
                    'color'             => ['Red','Blue','Green','Black','White'][rand(0,4)],
                    'size'              => ['S','M','L','XL'][rand(0,3)],
                    'price'             => $product->price,
                    'stock_quantity'    => rand(5,20),
                ]);
            }

            // Add 1-2 sample images (placeholder)
            for($img=0; $img<2; $img++){
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => 'products/placeholder.png', // put a placeholder.png in public/storage/products/
                    'is_primary' => $img === 0 ? 1 : 0,
                    'sort_order' => $img,
                ]);
            }
        }

        $this->command->info("500 sample products inserted successfully!");

        // php artisan db:seed --class=ProductSeeder
    }
}
