<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrdOrderDetail extends Model
{
    protected $table = 'ord_order_details';
    protected $fillable = [
        'order_id',
        'sku_id',
        'item_number',
        'item_description',
        'item_qty',
        'item_qty_return',
        'item_price',
        'item_line_discount',
        'item_discount',
        'item_tax',
        'created_at',
        'updated_at',
        'reg_status'
    ];
}
