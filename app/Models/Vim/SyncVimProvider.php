<?php

namespace App\Models\Vim;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncVimProvider extends Model
{
    //use HasFactory;

    protected $table = 'proveedores';
    public $timestamp = false;
    
    protected $fillable = [
        'id',
        'idproveedor',
        'nroidentificacion',
        'razonsocial',
        'idpais',
        'fecharegistro',
        'created_at'
    ];
}
