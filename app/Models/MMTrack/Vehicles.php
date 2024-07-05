<?php

namespace App\Models\MMTrack;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicles extends Model
{
     // Define la tabla de base de datos
     protected $table = 'vehicles';

     // Protección de campos de asignación masiva
     protected $fillable = [
         'driver_id',
         'vehicle_type_id',
         'placa',
         'description',
         'car_brands_id',
         'model',
         'color',
         'maximum_speed',
         'status',
         'action_status',
         'sede',
         'capacidad_kilogramos',
         'capacidad_volumen', 
     ];

     
   //============================
   // REALCIONES 
   //============================
   
   //relacion con la marca del vehiculo
   public function marca() {

      return $this->hasOne('App\Models\GenResourceDetail', 'id', 'car_brands_id');

   }   

   //relacion con la tipo del vehiculo
   public function tipo() {

      return $this->hasOne('App\Models\GenResourceDetail', 'id', 'vehicle_type_id');

   }

   //relacion con el conductor
   public function conductor() {

      return $this->belongsTo('App\Models\MMTrack\Drivers','driver_id');

   }   

   //relacion con el tracking
   public function tracking()
   {
      return $this->belongsTo('App\Models\MMTrack\DispatchTracking','vehicles_id');
   }


   //RELACION CON  DESPACHOS
   public function despachos()
   {
      return $this->hasMany('App\Models\MMTrack\OrderDelivery', 'vehiculo_id');
   } 

   //===========================
   // SCOPES PARA BUSQUEDAS
   //===========================
   
   //obtener vehiculos disponibles
   public function scopeDisponibles($query)
   {
      return $query->where('action_status','Disponible');
   }  
   
   //obtener vehiculos no disponibles
   public function scopeEnTaller($query)
   {
      return $query->where('action_status','En Taller');
   }  

   //obtener vehiculos preasignados
   public function scopePreasignados($query)
   {
      return $query->where('action_status','PRE-ASIGNADO');
   }    

   //obtener vehiculos en ruta
   public function scopeEnRuta($query)
   {
      return $query->where('action_status','En Ruta');
   } 


}
