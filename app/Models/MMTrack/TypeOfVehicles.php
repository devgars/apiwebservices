<?php

namespace App\Models\MMTrack;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeOfVehicles extends Model
{
    // Define la tabla de base de datos
    protected $table = 'vehicle_type';

    // Protección de campos de asignación masiva
    protected $fillable = [
        'name',
        'status'
    ];


     
   //============================
   // REALCIONES 
   //============================
        
    //relacion con el vehiculo
    public function vehiculo() {

        return $this->hasOne('App\Models\MMTrack\Vehicles', 'vehicle_type_id', 'id');
    }

}

