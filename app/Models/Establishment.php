<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Establishment extends Model
{
    protected $table = 'establishments';

    protected $fillable = [
        'code',
        'name',
        'description',
        'type_id',
        'parent_establishment_id',
        'region_id',
        'address',
        'contact_name',
        'phone_number',
        'email',
        'created_at',
        'updated_at',
        'order',
        'reg_status'
    ];
}
