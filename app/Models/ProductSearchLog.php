<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductSearchLog extends Model
{
    protected $table = 'product_search_logs';
    protected $fillable = [
        'id',
        'search_date',
        'search_time',
        'searched_product',
        'product_found',
        'customer_code',
        'ip',
        'created_at',
        'updated_at'
    ];
}
