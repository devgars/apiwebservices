<?php

namespace App\Models\MMTrack;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DispatchTracking extends Model
{
    // Define la tabla de base de datos
    protected $table = 'dispatch_tracking';

    // Protección de campos de asignación masiva
    protected $fillable = [
        'dispatch_id',
        'carrier_code',
        'means_of_transport',
        'placa_number',
        'document_type_provider',
        'document_number_supplier',
        'part_number',
        'pick_up_user',
        'contact',
        'department',
        'receptionist',
        'pick_up_code',
        'guide_series_number',
        'correlative_guide_number',
        'departure_date',
        'departure_time',
        'arrival_date',
        'arrival_time',
        'packaging_group',
        'transit_status_id',
        'status_office_almc',
        'observation',
        'carrier_group',
        'registration_status',
        'registration_user',
        'workstation',
        'date_of_registration',
        'check_in_time',
        'registration_scheduling',
        'user_movement',
        'movement_date',
        'time_movement',
        'vehicles_id',
        'drivers_id',
        'created_at',
        'updated_at',
        'status',
        'is_packed',
        'is_invoiced',
        'is_approved',

    ];


    
    //============================
    // REALCIONES 
    //============================
    
    //relacion con el pedido
    public function dispatch()
    {
        return $this->belongsTo('App\Models\MMTrack\Dispatch');
    }

    //relacion con el estatus de transito
    public function transit()
    {
        return $this->belongsTo('App\Models\MMTrack\Transit','transit_status_id');
    }

    //RELACION CON  MENSAJES
    public function details()
    {
        return $this->hasMany('App\Models\MMTrack\DispatchDetailsTracking', 'dispatch_tracking_id');
    } 

    //relacion con el vehiculo del pedido
    public function vehiculo() {

        return $this->hasOne('App\Models\MMTrack\Vehicles', 'id', 'vehicles_id');
    }
    

    //===========================
    // SCOPES PARA BUSQUEDAS
    //===========================
    //obtener llamadas del dia
    public function scopeDelDia($query)
    {
        return $query->whereDate('updated_at', date('Y-m-d'));
    } 

    //pedidos recibidos 
    public function scopeRecibidos($query)
    {
        return $query->where('transit_status_id',2);
    } 

    //pedidos en  ruta 
    public function scopeEnRuta($query)
    {
        return $query->where('transit_status_id',31);
    }

    //pedidos en preparacion
    public function scopeEnPreparacion($query)
    {
        return $query->whereIn('transit_status_id',[3,4,5,6,7,8,9]);
    }
    
    //pedidos en entregados
    public function scopeEntregados($query)
    {
        return $query->where('transit_status_id',32);
    }   


    
    
}
