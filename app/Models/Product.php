<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'subcategory_id',
        'brand_id',
        'name',
        'slug',
        'sku',
        'summary',
        'description',
        'price',
        'discount_price',
        'stock_quantity',
        'min_stock',
        'image',
        'is_active',
        'approval_status',
        'admin_remark',
        'is_featured',
        'is_on_sale',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'min_stock' => 'integer',
        'is_active' => 'boolean',
        'approval_status' => 'integer',
        'is_featured' => 'boolean',
        'is_on_sale' => 'boolean',
    ];

    const STATUS_PENDING = 1;
    const STATUS_APPROVED = 2;
    const STATUS_REJECTED = 3;

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($product) {

            if (empty($product->slug) || $product->isDirty('name')) {
                $baseSlug = Str::slug($product->name);
                $newSlug = $baseSlug . '-' . Str::lower(Str::random(4));
                $exists = static::where('category_id', $product->category_id)
                    ->where('slug', $newSlug)
                    ->where('id', '!=', $product->id)
                    ->exists();

                if ($exists) {
                    $newSlug = $baseSlug . '-' . Str::lower(Str::random(6));
                }
                $product->slug = $newSlug;
            }
        });
    }

    // Relationships
    public function category()
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function subcategory()
    {
        return $this->belongsTo(ProductSubCategory::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class, 'product_id');
    }

    public function stock()
    {
        return $this->hasMany(Stock::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class, 'product_id');
    }


    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeApproved($query)
    {
        return $query->where('approval_status', self::STATUS_APPROVED);
    }

    public function scopePublished($query)
    {
        return $query->active()->approved();
    }
}
