<?php

namespace App\Models\Vim;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncVimCustomer extends Model
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
