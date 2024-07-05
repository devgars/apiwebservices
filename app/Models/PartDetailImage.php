<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartDetailImage extends Model
{
    protected $table = 'part_detail_images';

    protected $fillable = [
        'part_detail_id',
        'image',
        'is_360',
        'created_at',
        'updated_at',
    ];
}
