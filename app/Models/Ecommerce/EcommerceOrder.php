<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcommerceOrder extends Model
{
    // Define la tabla de base de datos
    protected $table = 'ecommerce_orders';

    // Protección de campos de asignación masiva
    protected $fillable = [
        'ecommerce_order_number',
        'json_order',
        'created_at',
        'updated_at'
    ];
}
