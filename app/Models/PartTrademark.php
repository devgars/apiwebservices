<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartTrademark extends Model
{
    // Define la tabla de base de datos
    protected $table = 'part_trademarks';
    protected $fillable = [
        'id',
        'code',
        'abrv',
        'short_name',
        'name',
        'created_at',
        'updated_at',
        'reg_status',
    ];
}
