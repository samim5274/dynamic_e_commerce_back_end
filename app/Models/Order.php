<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reg',
        'slug',
        'date',

        'user_id',

        'amount',
        'discount',
        'payable_amount',
        'currency',
        'point',

        'payment_method',
        'transaction_id',
        'is_paid',
        'paid_at',

        'status',

        'contact_number',
        'shipping_address',

        'confirmed_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'payable_amount' => 'decimal:2',
        'is_paid' => 'boolean',
        'paid_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'point' => 'integer',
    ];

    // Auto slug generate
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->slug)) {
                $order->slug = self::generateSlug($order);
            }
        });
    }

    private static function generateSlug($order)
    {
        $base = Str::slug('order-' . $order->reg . '-' . Str::uuid());

        // ensure unique slug
        $count = static::where('slug', 'like', "{$base}%")->count();

        return $count ? "{$base}-" . ($count + 1) : $base;
    }

    // Relation
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
