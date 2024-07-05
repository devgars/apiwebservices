<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CurrencyExchangeRate extends Model
{
    protected $table = 'currency_exchange_rates';

    protected $fillable = [
        'currency_code',
        'reg_date',
        'official_buying_price',
        'official_selling_price',
        'official_average_price',
        'mym_buying_price',
        'mym_selling_price',
        'mym_average_price',
        'created_at',
        'updated_at',
        'reg_status',
    ];
}
