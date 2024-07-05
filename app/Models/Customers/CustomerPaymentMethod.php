<?php

namespace App\Models\Customers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerPaymentMethod extends Model
{
    protected $table = 'customer_payment_methods';

    protected $fillable = [
        'customer_id',
        'payment_method_id',
        'payment_modality_id',
        'payment_condition_id',
        'reg_status',
        'created_at',
        'updated_at'
    ];
}
