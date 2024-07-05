<?php

namespace App\Models\MMTrack;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderDelivery extends Model
{

    // Define la tabla de base de datos
    protected $table = 'order_delivery';

    // Protección de campos de asignación masiva
    protected $fillable = [
        'vehiculo_id',
        'type',
        'status',
        'assistant_id'
    ];


    
    //RELACION CON  MENSAJES
    public function details()
    {
        return $this->hasMany('App\Models\MMTrack\OrderDeliveryDetail', 'order_delivery_id');
    } 

    //RELACION CON  EL vehiculo
    public function Vehicles()
    {
        return $this->belongsTo('App\Models\MMTrack\DispatchTracking', 'vehiculo_id');
    } 

    //RELACION CON  EL VEHICULO
    public function Vehiculo()
    {
        return $this->belongsTo('App\Models\MMTrack\Vehicles', 'vehiculo_id');
    } 

    
}
