<?php

namespace App\Models\PartOffers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartOfferGroup extends Model
{
    protected $table = 'part_offer_groups';
    protected $fillable = [
        'id',
        'company_id',
        'offer_id',
        'offer_description',
        'offer_type_id',
        'origin_code',
        'company_group_id',
        'currency_code',
        'init_date',
        'end_date',
        'reg_status',
        'created_at',
        'updated_at',
    ];
}
