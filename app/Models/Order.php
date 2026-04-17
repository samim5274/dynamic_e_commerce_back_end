<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'reg',
        'date',
        'user_id',
        'transaction_id',
        'status',
        'total',
        'paid_at',
    ];
}
