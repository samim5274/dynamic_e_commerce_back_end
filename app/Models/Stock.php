<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;

    protected $fillable = [
        'reg',         // Registration or Batch Number
        'date',        // Transaction Date
        'product_id',  // Related Product
        'stockIn',
        'stockOut',
        'remark',
        'status',      // active, pending, adjusted etc.
    ];

    protected $casts = [
        'date' => 'date',
        'stockIn' => 'integer',
        'stockOut' => 'integer',
        'status' => 'string',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // e.g Stock::stockInOnly()->get();
    public function scopeStockInOnly($query)
    {
        return $query->where('stockIn', '>', 0);
    }

    public function scopeStockOutOnly($query)
    {
        return $query->where('stockOut', '>', 0);
    }

    public function getBalanceAttribute()
    {
        return $this->stockIn - $this->stockOut;
    }
}
