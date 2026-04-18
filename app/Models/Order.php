<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'reg',
        'date',
        'user_id',
        'transaction_id',
        'currency',
        'status',
        'amount',
        'slug',
        'point',
        'paid_at',
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
