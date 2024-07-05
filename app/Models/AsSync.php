<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AsSync extends Model
{
    protected $table = 'as_sync';
    
    protected $fillable = [
        'sytabla',
        'sql',
        'usuario',
        'fecha_generado',
        'hora_generado',
        'tipo_operacion',
        'created_at',
        'updated_at'
    ];
}
