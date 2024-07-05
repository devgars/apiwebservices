<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerCreditNoteDetail extends Model
{
    protected $table = 'customer_credit_note_details';

    protected $fillable = [
        'credit_note_id',
        'part_detail_id',
        'item1',
        'item2',
        'returned_quantity',
        'price',
        'line_discount',
        'additional_discount',
        'tax_rate',
        'created_at',
        'updated_at',
        'reg_status'
    ];
}
