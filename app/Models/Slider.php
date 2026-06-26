<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Slider extends Model
{
    use HasFactory;

    protected $fillable = [
        'tag',
        'title',
        'description',

        'image',

        'button_text',
        'button_link',

        'sort_order',

        'status'
    ];
}
