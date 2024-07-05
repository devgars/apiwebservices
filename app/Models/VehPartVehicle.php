<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehPartVehicle extends Model
{
    protected $table = 'veh_part_vehicles';
    protected $fillable = [
        'part_id',
        'vehicle_id',
        'veh_order',
        'reg_status',
        'created_at',
        'updated_at',
    ];
}
