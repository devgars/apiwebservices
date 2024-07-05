<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerAddress extends Model
{
    // Define la tabla de base de datos
    protected $table = 'customer_addresses';

    // Protección de campos de asignación masiva
    protected $fillable = [
        'id',
        'customer_id',
        'address_order',
        'address_type_id',
        'road_type_id',
        'road_name',
        'number',
        'apartment',
        'floor',
        'block',
        'allotment',
        'zone_type_id',
        'zone_name',
        'country_id',
        'region_id',
        'contact_name',
        'contact_phone',
        'contact_email',
        'created_at',
        'updated_at',
        'reg_status',
        'address_description'
    ];



    //relacion con  los clientes
    public function customers()
    {

        return $this->belongsTo('App\Models\Customer', 'customer_id', 'id');
    }
}
