<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehModel extends Model
{
    protected $table = 'veh_models';
    protected $fillable = [
        'line_id',
        'model_code',
        'model_description',
        'reg_status',
        'created_at',
        'updated_at',
    ];
}
