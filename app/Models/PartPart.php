<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartPart extends Model
{
    protected $table = 'part_parts';
    protected $fillable = [
        'id',
        'line_id',
        'code',
        'short_name',
        'name',
        'measure_unit_id',
        'subsystem_id',
        'reg_status',
        'created_at',
        'updated_at',
    ];
}
