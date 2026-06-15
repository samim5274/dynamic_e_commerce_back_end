<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PointTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type', 'points', 'matching_count',
        'bonus_amount', 'bonus_status',
        'source', 'month','rank' ,'reference_id', 'note'
    ];

    protected $casts = [
        'bonus_amount' => 'decimal:2',
        'points'       => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function referenceUser(){
        return $this->belongsTo(User::class, 'reference_id');
    }
}
