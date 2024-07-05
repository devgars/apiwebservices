<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    protected $table = 'purchase_orders';
    protected $fillable = [
        'company_id',
        'subsidiary_id',
        'warehouse_id',
        'provider_id',
        'currency_id',
        'purchase_number',
        'reg_date',
        'estimated_delivery_date',
        'discount_rate_1',
        'discount_rate_2',
        'tax_rate',
        'total_amount',
        'discount_amount_1',
        'discount_amount_2',
        'freight_amount',
        'outlay_amount',
        'tax_amount',
        'net_amount',
        'created_at',
        'updated_at',
        'reg_status',
        'purchase_description',
        'provider_code'
    ];
}
