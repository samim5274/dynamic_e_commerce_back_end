<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Basic Info
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:100', 'unique:products,sku'],

            // Relations
            'brand' => ['required', 'integer', 'exists:brands,id'],
            'category' => ['required', 'integer', 'exists:product_categories,id'],
            'subcategory' => ['required', 'integer', 'exists:product_sub_categories,id'],

            // Pricing & Stock
            'price' => ['required', 'numeric', 'min:0'],
            'discount_price' => ['nullable', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'min_stock' => ['nullable', 'integer', 'min:0'],

            // Content
            'summary' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:products,slug'],

            // SEO
            'title' => ['nullable', 'string', 'max:70'],
            'keywords' => ['nullable', 'string', 'max:200'],
            'meta_description' => ['nullable', 'string', 'max:1000'],

            // Status
            'is_featured' => ['nullable', 'boolean'],
            'is_on_sale' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],

            // Images
            'images' => ['nullable', 'array'],
            'images.*' => ['file', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],

            // Variants (Color + Size)
            'variants' => ['nullable', 'array'],
            'variants.*.color' => ['required_with:variants', 'string', 'max:50'],
            'variants.*.size' => ['required_with:variants', 'string', 'max:50'],
            'variants.*.price' => ['required_with:variants', 'numeric', 'min:0'],
            'variants.*.stock' => ['required_with:variants', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Product name is required.',
            'sku.required' => 'SKU is required.',
            'sku.unique' => 'This SKU already exists.',

            'brand.required' => 'Please select a brand.',
            'category.required' => 'Please select a category.',
            'subcategory.required' => 'Please select a subcategory.',

            'price.required' => 'Price is required.',
            'price.min' => 'Price must be greater than 0.',

            'stock_quantity.required' => 'Stock quantity is required.',

            'images.*.file' => 'Each upload must be a valid file.',
            'images.*.image' => 'Each file must be an image.',
            'images.*.mimes' => 'Allowed image types: jpg, jpeg, png, webp, gif.',
            'images.*.max' => 'Each image must be under 2MB.',

            'variants.*.color.required_with' => 'Variant color is required.',
            'variants.*.size.required_with' => 'Variant size is required.',
            'variants.*.price.required_with' => 'Variant price is required.',
            'variants.*.stock.required_with' => 'Variant stock is required.',
        ];
    }
}
