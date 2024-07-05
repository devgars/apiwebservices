<?php

namespace App\Models\MMTrack;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Drivers extends Model
{

    // Define la tabla de base de datos
    protected $table = 'drivers';

    // Protección de campos de asignación masiva
    protected $fillable = [
        'names',
        'surnames',
        'document_type_id',
        'document_number',
        'status',
        'user_id'
    ];

    //=============================
    //RELACIONES
    //=============================

    //relacion con el vehiculo
    public function vehiculo() {

        return $this->hasOne('App\Models\MMTrack\Vehicles', 'driver_id', 'id');
    }

    //relacion con el usuario de mmtrack
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

}
