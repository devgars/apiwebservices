<?php

namespace App\Models\Vistas;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsignmentWarehouses extends Model
{

    protected $table = 'v_almacenes_consignacion';

    protected $fillable = [
        'id',
        'code',
        'name',
        'description',
        'address',
        'order',
        'reg_status'
    ];
}
