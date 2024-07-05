<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ubigeo extends Model
{
    use HasFactory;

    // Define la tabla de base de datos
    protected $table = 'ubigeos';

    // Protección de campos de asignación masiva
    protected $fillable = [
        'code',
        'ubigeo_type_id',
        'abrv',
        'name',
        'ubigeo',
        'flag_image',
        'phone_code',
        'parent_ubigeo_id',
        'order',
        'reg_status',
        'created_at',
        'updated_at',
    ];

    //==================
    // RELACIONES
    //==================


    public function agencias() {
        return $this->belongsToMany('App\Models\MMTrack\AgencyBranches');
    }


    //==================
    //SCOPES
    //==================
    //obtener LOS DEPARTAMENTOS
    public function scopeDepartamentos($query)
    {
        return $query->where('ubigeo_type_id', 2);
    }

    //obtener registros activos
    public function scopeActivos($query)
    {
        return $query->where('reg_status', 1);
    }

}
