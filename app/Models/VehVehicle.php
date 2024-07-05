<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehVehicle extends Model
{
    protected $table = 'veh_vehicles';
    protected $fillable = [
        'model_id',
        'vin',
        'veh_year',
        'veh_hp',
        'veh_traction',
        'veh_engine',
        'veh_gearbox',
        'veh_front_axle',
        'veh_rear_axle',
        'veh_category_code',
        'veh_order',
        'reg_status',
        'created_at',
        'updated_at',
    ];
}
