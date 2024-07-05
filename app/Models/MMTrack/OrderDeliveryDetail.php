<?php

namespace App\Models\MMTrack;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderDeliveryDetail extends Model
{
    // Define la tabla de base de datos
    protected $table = 'order_delivery_detail';

    // Protección de campos de asignación masiva
    protected $fillable = [
        'order_delivery_id',
        'dispatch',
        'customer_id',
        'shipping_type',
        'status_order',
        'start_date',
        'deliver_date',
        'status',
        'imagen',
        'image_64',
        'route_start_date',
        'arrival_date',
        'peso',
        'bultos',
        'observacion',
        'tipo_entrega',
        'imagen_bultos',
        'image_64_bultos',
        'assistant',
        'agency_date'
    ];


    
    //==============================
    // RELACIONES
    //==============================

    //RELACION CON  EL PEDIDO
    public function pedido()
    {
    return $this->belongsTo('App\Models\MMTrack\Dispatch','dispatch');
    } 

    //RELACION CON  EL DESPACHO
    public function despacho()
    {
        return $this->belongsTo('App\Models\MMTrack\OrderDelivery', 'order_delivery_id');
    } 


    //RELACION CON  EL CLIENTE
    public function cliente()
    {
    return $this->belongsTo('App\Models\Customer','customer_id');
    } 


}
