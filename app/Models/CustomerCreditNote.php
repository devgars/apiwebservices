<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerCreditNote extends Model
{
    protected $table = 'customer_credit_notes';

    protected $fillable = [
        'company_id',
        'subsidiary_id',
        'warehouse_id',
        'credit_note_number',
        'return_type_code',
        'reason_type_code',
        'credit_note_date',
        'seller_id',
        'condition_payment_discount_rate',
        'customer_class_discount_rate',
        'total_amount',
        'condition_payment_discount_amount',
        'customer_class_discount_amount',
        'tax_amount',
        'document_type_code',
        'created_at',
        'updated_at',
        'reg_status',
        'serie',
        'correlative'
    ];
}
