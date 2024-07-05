<?php

namespace App\Models\MMTrack;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverAssistant extends Model
{

    // Define la tabla de base de datos
    protected $table = 'driver_assistant';

    // Protección de campos de asignación masiva
    protected $fillable = [
        'names',
        'surnames',
        'document_type_id',
        'document_number',
        'email',
        'status',
        'user_id'
    ];

    //=============================
    //RELACIONES
    //=============================

    //RELACION CON  DESPACHOS
    public function despachos()
    {
        return $this->hasMany('App\Models\MMTrack\OrderDelivery', 'assistant_id');
    } 

    //relacion con el usuario de mmtrack
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    //relacion con el tracking del pedido
    public function document() {

        return $this->hasOne('App\Models\GenResourceDetail', 'id', 'document_type_id');
    }

}