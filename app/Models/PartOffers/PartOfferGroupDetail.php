<?php

namespace App\Models\PartOffers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartOfferGroupDetail extends Model
{
    protected $table = 'part_offer_group_details';
    protected $fillable = [
        'id',
        'part_offer_group_id',
        'part_detail_id',
        'offer_price',
        'discount_rate',
        'reg_status',
        'created_at',
        'updated_at',
    ];
}
