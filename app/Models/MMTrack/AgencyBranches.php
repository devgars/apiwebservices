<?php

namespace App\Models\MMTrack;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgencyBranches extends Model
{

    // Define la tabla de base de datos
    protected $table = 'agency_branches';

    // Protección de campos de asignación masiva
    protected $fillable = [
        'agencia_id',
        'carrier_code',
        'name',
        'status', 
    ];

    //=============================
    //RELACIONES
    //=============================

    public function Agencia()
    {
        return $this->belongsTo('App\Models\GenResourceDetail');
    }


    public function Departementos() {
        return $this->belongsToMany('App\Models\Ubigeo');
    }

}
