<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'reg',
        'product_id',
        'variant_id',
        'user_id',
        'quantity',
        'price',
        'discount',
        'note',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price'    => 'decimal:2',
        'discount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    // === Accessors & Logic ===

    public function getTotalPriceAttribute(): float
    {
        return ($this->price - $this->discount) * $this->quantity;
    }

    public function scopeCurrent($query, $regId)
    {
        return $query->where('user_id', auth()->id())
                     ->orWhere('reg', $regId);
    }
}
