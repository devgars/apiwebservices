<?php

namespace App\Models\MMTrack;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarBrands extends Model
{
    // Define la tabla de base de datos
    protected $table = 'car_brands';

    // Protección de campos de asignación masiva
    protected $fillable = [
        'name',
        'status'
    ];


               
    //relacion con el vehiculo
    public function vehiculo() {

        return $this->hasOne('App\Models\MMTrack\Vehicles', 'car_brands_id', 'id');
    }

}
