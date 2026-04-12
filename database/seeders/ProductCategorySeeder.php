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
            'Medicines',
            'Medical Devices',
            'Health Care Equipment',
            'Personal Care',
            'Baby Care',
            'Diabetic Care',
            'Heart & Blood Pressure',
            'Vitamins & Supplements',
            'Sexual Wellness',
            'First Aid',
            'Lab Test & Diagnostics',
            'Elderly Care',
            'Orthopedic Care',
            'Covid & Safety',
            'Herbal & Ayurvedic',
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
