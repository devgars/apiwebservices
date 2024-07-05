<?php

namespace App\Models\Vim;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncVimArticle extends Model
{
    //use HasFactory;

    protected $table = 'articulos';
    public $timestamp = false;
    
    protected $fillable = [
        'id',
        'code',
        'descripcion',
        'unidad_medida',
        'created_at',
        'linea_code'
    ];
}
