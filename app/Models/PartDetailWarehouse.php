<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartDetailWarehouse extends Model
{
    protected $table = 'part_detail_warehouses';

    protected $fillable = [
        'part_detail_id',
        'warehouse_id',
        'init_qty',
        'in_qty',
        'out_qty',
        'in_warehouse_stock',
        'reg_status',
        'created_at'
    ];
}
