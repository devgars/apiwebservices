<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModelClientJT extends Model
{
    //use HasFactory;
    protected $table = 'clientes';
    public $timestamp = false;

    protected $fillable = [
        'id',
        'nrodocidentida',
        'idcliente',
        'fecharegistro',
        'razonsocial',
        'nombrecomercial',
        'created_at'
    ];
}
