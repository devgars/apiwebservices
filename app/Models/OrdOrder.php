<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrdOrder extends Model
{
    protected $table = 'ord_orders';
    protected $fillable = [
        'company_id',
        'subsidiary_id',
        'customer_id',
        'document_type_id',
        'order_number',
        'order_date',
        'order_time',
        'origin_id',
        'quote_id',
        'seller_id',
        'attended_by_user_id',
        'currency_id',
        'payment_type_id',
        'payment_condition_id',
        'credit_days',
        'warehouse_id',
        'delivery_type_id',
        'carrier_id',
        'discount',
        'discount_customer_class',
        'discount_payment_type',
        'subtotal',
        'igv_tax',
        'total_tax',
        'total',
        'created_at',
        'updated_at',
        'reg_status',
        'reg_order_doc_status',
        'customer_contact_id'
    ];
}
