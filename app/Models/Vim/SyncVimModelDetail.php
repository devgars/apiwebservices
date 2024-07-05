<?php

namespace App\Models\Vim;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncVimModelDetail extends Model
{
    //use HasFactory;

    protected $table = 'modelo_detalles';
    public $timestamp = false;
    
    protected $fillable = [
        'id',
        'anio',
        'hp',
        'traccion',
        'motor',
        'caja_cambio',
        'corona',
        'eje_delantero',
        'eje_trasero',
        'fecharegistro',
        'created_at',
        'idmodelo',
        'idlinea',
        'idtipovehiculo'
    ];
}
