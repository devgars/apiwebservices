<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartOfferDetail extends Model
{
    protected $table = 'part_offer_details';
    protected $fillable = [
        'id',
        'offer_id',
        'part_detail_id',
        'list_price',
        'min_price',
        'cost_price',
        'discount_rate',
        'profit_rate',
        'new_profit_rate',
        'base_factor',
        'reg_status',
        'created_at',
        'updated_at',
    ];
}
