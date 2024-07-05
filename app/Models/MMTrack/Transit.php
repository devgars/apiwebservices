<?php

namespace App\Models\MMTrack;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transit extends Model
{
    // Define la tabla de base de datos
    protected $table = 'transit_status';
    public $timestamps = false;
    
    // Protección de campos de asignación masiva
    protected $fillable = [
        'description_as',
        'description_web',
        'mensaje',
        'status',
    ];


    //============================
    // REALCIONES 
    //============================
           
    //relacion con el tracking del pedido
    public function tracking() {

        return $this->hasOne('App\Models\MMTrack\DispatchTracking', 'transit_status_id', 'id');
    }

    //relacion con el tracking del pedido
    public function seguimiento() {

        return $this->hasOne('App\Models\MMTrack\DispatchDetailsTracking', 'transit_status_id', 'id');
    }
}
