<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartPartDetail extends Model
{
    // Define la tabla de base de datos
    protected $table = 'part_part_details';
    protected $fillable = [
        'id',
        'part_id',
        'line_id',
        'origin_id',
        'trademark_id',
        'created_at',
        'updated_at',
        'reg_status',
        'sku',
        'factory_code',
        'rotation',
        'principal_image',
        'weight',
        'list_price',
        'min_price',
        'currency_code',
        'total_stock',
        'technical_spec',
    ];
}
