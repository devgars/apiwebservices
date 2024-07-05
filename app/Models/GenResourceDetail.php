<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class GenResourceDetail extends Model
{
    // Define la tabla de base de datos
    protected $table = 'gen_resource_details';

    // Protección de campos de asignación masiva
    protected $fillable = [
        'id',
        'resource_id',
        'code',
        'abrv',
        'name',
        'description',
        'order',
        'reg_status',
        'parent_resource_detail_id',
        'created_at',
        'updated_at',
    ];


    //relacion con el pedido
    public function CustomerCompany()
    {
        return $this->belongsTo('App\Models\MMTrack\customers');
    } 

    //relacion con el pedido
    public function  CustomerDocument()
    {
        return $this->belongsTo('App\Models\MMTrack\customers');
    }

    //relacion con el pedido
    public function  AssistantDocument()
    {
        return $this->belongsTo('App\Models\MMTrack\DriverAssistant');
    }

    //relacion con el pedido
    public function  VehiculoTipo()
    {
        return $this->belongsTo('App\Models\MMTrack\Vehicles');
    }

    //relacion de las agencias con las sucursales
    public function Sucursales()
    {
        return $this->hasMany('App\Models\MMTrack\AgencyBranches','agencia_id');
    }


    //==================
    //SCOPES
    //==================
     
    //obtener registros activos
    public function scopeActivos($query)
    {
        return $query->where('reg_status', 1);
    }
}