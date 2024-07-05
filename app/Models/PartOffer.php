<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartOffer extends Model
{
    protected $table = 'part_offers';
    protected $fillable = [
        'id',
        'company_id',
        'offer_code',
        'offer_description',
        'discount_state',
        'type_offer_id',
        'year_offer',
        'init_date',
        'end_date',
        'reg_status',
        'created_at',
        'updated_at',
    ];
}
