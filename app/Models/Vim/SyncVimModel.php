<?php

namespace App\Models\Vim;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncVimModel extends Model
{
    //use HasFactory;

    protected $table = 'modelos';
    public $timestamp = false;
    
    protected $fillable = [
        'id',
        'idmodelo',
        'fecharegistro',
        'codlinea',
        'codarticulo',
        'codlineamodelo',
        'created_at'
    ];
}
