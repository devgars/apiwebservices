<?php

namespace App\Models\Vistas;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Direcciones extends Model
{
    public $timestamps = false;

    // Define la tabla de base de datos
    protected $table = 'v_direcciones';

    // Protección de campos de asignación masiva
    protected $fillable = [
        'customer_id',
        'customer_code',
        'address_order',
        'tipo_direccion_id',
        'tipo_direccion',
        'direccion_completa',
        'region',
        'road_name',
        'number',
        'tipo_zona',
        'zone_name',
        'pais',
        'dpto_code'
    ];

        
    //relacion con  los clientes
    public function customers() {

        return $this->belongsTo('App\Models\Customer', 'customer_id', 'id');
    }
    
}
