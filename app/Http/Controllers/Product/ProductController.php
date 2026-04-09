<?php

namespace App\Http\Controllers\Product;

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

class ProductController extends Controller
{
    public function index(){
        try{
            $products = Product::with([
                'category:id,name',
                'subcategory:id,name',
                'brand:id,name',
                ])
                ->get();

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

    public function store(StoreProductRequest $request){

        $user = auth('sanctum')->user();
        $data = $request->validated();

        try {
            DB::beginTransaction();

            $product = new Product();

            $product->name = $data['name'];
            $product->sku = $data['sku'];

            $product->brand_id = $data['brand'];
            $product->category_id = $data['category'];
            $product->subcategory_id = $data['subcategory'];

            $product->price = $data['price'];
            $product->discount_price = $data['discount_price'] ?? 0;
            $product->stock_quantity = $data['stock_quantity'];
            $product->min_stock = $data['min_stock'] ?? 0;

            $product->summary = $data['summary'] ?? null;
            $product->description = $data['description'] ?? null;
            $product->slug = $data['slug'] ?? null;

            $product->meta_title = $data['title'] ?? null;
            $product->meta_keywords = $data['keywords'] ?? null;
            $product->meta_description = $data['meta_description'] ?? null;

            $product->is_featured = $data['is_featured'] ?? false;
            $product->is_on_sale = $data['is_on_sale'] ?? false;
            $product->is_active = $data['is_active'] ?? true;

            $product->save();

            // save product variants if provided
            if ($request->has('variants') && is_array($request->variants)) {
                foreach ($request->variants as $variant) {
                    ProductVariant::create([
                        'product_id' => $product->id,
                        'color' => $variant['color'] ?? null,
                        'size' => $variant['size'] ?? null,
                        'price' => $variant['price'] ?? 0,
                        'stock' => $variant['stock'] ?? 0,
                    ]);
                }
            }

            // save product images and get their paths
            $images = $this->storeProductImages($request, $product->id, $user);
            $product->images = $images;

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully.',
                'data' => $product
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => "Product can not created. Error: " . $e->getMessage(),
            ], 500);
        }
    }

    private function storeProductImages(Request $request, $productId, $user)
    {
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $path = $image->store("products/", 'public');
                ProductImage::create([
                    'product_id' => $productId,
                    'image_path' => $path,
                    'is_primary' => $index === 0,
                    'sort_order' => $index
                ]);
            }
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

    public function edit(Request $request, $id){
        // ❗ Only product details can be updated, not the images and variants
        $user = auth('sanctum')->user();
        $data = $request->all();

        try {
            $product = Product::where('id', $id)->firstOrFail();

            // ------------------------
            // Delete old images if new images uploaded
            // ------------------------
            if ($request->hasFile('images')) {
                foreach ($product->images as $oldImage) {
                    // delete from storage
                    if (\Storage::disk('public')->exists($oldImage->image_path)) {
                        \Storage::disk('public')->delete($oldImage->image_path);
                    }
                    // delete from database
                    $oldImage->delete();
                }

                // save new images
                foreach ($request->file('images') as $index => $image) {
                    $path = $image->store("products/", 'public');
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => $path,
                        'is_primary' => $index === 0 ? 1 : 0,
                        'sort_order' => $index
                    ]);
                }
            }

            // Basic fields
            $product->name = $data['name'] ?? $product->name;
            $product->sku = $data['sku'] ?? $product->sku;
            $product->brand_id = $data['brand'] ?? $product->brand_id;
            $product->category_id = $data['category'] ?? $product->category_id;
            $product->subcategory_id = $data['subcategory'] ?? $product->subcategory_id;

            // Pricing & stock
            $product->price = $data['price'] ?? $product->price;
            $product->discount_price = $data['discount_price'] ?? $product->discount_price;
            $product->stock_quantity = $data['stock_quantity'] ?? $product->stock_quantity;
            $product->min_stock = $data['min_stock'] ?? $product->min_stock;

            // Description & summary
            $product->summary = $data['summary'] ?? $product->summary;
            $product->description = $data['description'] ?? $product->description;

            // meta fields
            $product->meta_title = $data['title'] ?? $product->meta_title;
            $product->meta_keywords = $data['keywords'] ?? $product->meta_keywords;
            $product->meta_description = $data['meta_description'] ?? $product->meta_description;

            // status fields
            if (isset($data['is_featured'])) {
                $product->is_featured = filter_var($data['is_featured'], FILTER_VALIDATE_BOOLEAN);
            }
            if (isset($data['is_on_sale'])) {
                $product->is_on_sale = filter_var($data['is_on_sale'], FILTER_VALIDATE_BOOLEAN);
            }
            if (isset($data['is_active'])) {
                $product->is_active = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN);
            }

            // save updated product
            if ($product->isDirty()) {
                // only update if there are changes
                if ($product->save()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Product updated successfully.',
                        'data' => $product
                    ], 200);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Product can not updated.',
                    ], 500);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No changes detected to update.',
                ], 400);
            }
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => "Product can not updated. Error: " . $e->getMessage(),
            ], 500);
        }
    }

    public function delete($id){
        DB::beginTransaction();

        try {
            $product = Product::with(['images', 'variants'])->findOrFail($id);

            // Delete images from storage + DB
            foreach ($product->images as $img) {
                if ($img->image_path && Storage::disk('public')->exists($img->image_path)) {
                    Storage::disk('public')->delete($img->image_path);
                }
                $img->delete(); // DB
            }

            // Delete variants
            foreach ($product->variants as $variant) {
                $variant->delete();
            }

            // Delete product
            $product->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully.'
            ], 200);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Product not found.'
            ], 404);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Delete failed. ' . $e->getMessage()
            ], 500);
        }
    }
}
