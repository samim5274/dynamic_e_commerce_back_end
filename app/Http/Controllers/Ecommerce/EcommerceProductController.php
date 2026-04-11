<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use App\Http\Requests\StoreProductRequest;
use App\Models\User;
use App\Models\ProductCategory;
use App\Models\ProductSubCategory;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Stock;
use App\Models\ProductVariant;
use App\Models\ProductImage;

class EcommerceProductController extends Controller
{
    public function index(){
        try{
            $products = Product::with([
                    'category:id,name',
                    'subcategory:id,name',
                    'brand:id,name',
                ])
                ->latest()
                ->get()
                ->groupBy('category_id')
                ->map(function ($items) {
                    return $items->take(10);
                });

            return response()->json([
                'success' => true,
                'message' => 'Products fetched successfully.',
                'data' => $products
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Products can not fetched.',
            ], 500);
        }
    }

    public function getCategory(){
        try{
            $productCategories = ProductCategory::all();
            return response()->json([
                'success' => true,
                'data' => $productCategories
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product categories can not fetched.',
            ], 500);
        }
    }

    public function getSubCategory(){
        try{
            $productSubCategories = ProductSubCategory::with('category:id,name')->get();
            return response()->json([
                'success' => true,
                'data' => $productSubCategories
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product sub categories can not fetched.',
            ], 500);
        }
    }

    public function getBrand(){
        try{
            $productBrands = Brand::all();
            return response()->json([
                'success' => true,
                'data' => $productBrands
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Brands can not fetched.',
            ], 500);
        }
    }

    public function show($slug){
        try {
            $product = Product::with([
                'category:id,name',
                'subcategory:id,name',
                'brand:id,name',
                'variants',
                'images'
            ])->where('slug', $slug)->firstOrFail();

            return response()->json([
                'success' => true,
                'message' => 'Product fetched successfully.',
                'data' => $product
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => "Product can not fetched. Error: " . $e->getMessage(),
            ], 500);
        }
    }

}
