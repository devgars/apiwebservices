<?php

namespace App\Models\Vim;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AsSyncVim extends Model
{
    //use HasFactory;

    protected $table = 'as_sync';
    public $timestamp = false;
    
    protected $fillable = [
        'id',
        'sytabla',
        'sql',
        'usuario',
        'fecha_generado',
        'hora_generado',
        'tipo_operacion'
    ];
}
