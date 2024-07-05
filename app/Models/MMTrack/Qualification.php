<?php

namespace App\Models\MMTrack;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Qualification extends Model
{
    // Define la tabla de base de datos
    protected $table = 'qualification';
    public $timestamps = false;
    
    // Protección de campos de asignación masiva
    protected $fillable = [
        'id',
        'fecha',
        'valor',
        'dispatch_id'
    ];


    //============================
    // REALCIONES 
    //============================
           
    //relacion con el pedido
    public function dispatch()
    {
        return $this->belongsTo('App\Models\MMTrack\Dispatch');
    }
 
}

