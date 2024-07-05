<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    protected $table = 'providers';
    protected $fillable = [
        'code',
        'identification_number',
        'name',
        'country_code',
        'provider_type_code',
        'created_at',
        'updated_at',
        'reg_status',
    ];
}
