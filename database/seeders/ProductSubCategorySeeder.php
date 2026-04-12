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
            'Medicines' => [
                'Tablet',
                'Capsule',
                'Syrup',
                'Injection',
                'Ointment'
            ],

            'Medical Devices' => [
                'Thermometer',
                'Blood Pressure Monitor',
                'Glucometer',
                'Pulse Oximeter'
            ],

            'Health Care Equipment' => [
                'Nebulizer',
                'Oxygen Cylinder',
                'Wheelchair',
                'Hospital Bed'
            ],

            'Personal Care' => [
                'Skin Care',
                'Hair Care',
                'Oral Care',
                'Hygiene Products'
            ],

            'Baby Care' => [
                'Baby Lotion',
                'Diapers',
                'Baby Shampoo',
                'Feeding Bottle'
            ],

            'Diabetic Care' => [
                'Glucose Monitor',
                'Test Strips',
                'Diabetic Food'
            ],

            'Heart & Blood Pressure' => [
                'BP Machine',
                'Heart Monitor',
                'Cholesterol Test'
            ],

            'Vitamins & Supplements' => [
                'Vitamin C',
                'Vitamin D',
                'Calcium',
                'Protein Powder'
            ],

            'Sexual Wellness' => [
                'Condom',
                'Lubricants',
                'Pregnancy Test Kit'
            ],

            'First Aid' => [
                'Bandage',
                'Antiseptic',
                'Cotton',
                'Pain Relief Spray'
            ],

            'Lab Test & Diagnostics' => [
                'Blood Test Kit',
                'Urine Test Kit',
                'Covid Test Kit'
            ],

            'Elderly Care' => [
                'Walking Stick',
                'Adult Diapers',
                'Support Belt'
            ],

            'Orthopedic Care' => [
                'Knee Support',
                'Back Support',
                'Neck Collar'
            ],

            'Covid & Safety' => [
                'Face Mask',
                'Hand Sanitizer',
                'Gloves'
            ],

            'Herbal & Ayurvedic' => [
                'Herbal Medicine',
                'Natural Oil',
                'Supplements'
            ],
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
