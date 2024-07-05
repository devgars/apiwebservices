<?php

namespace App\Models\Vim;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncVimArticleDetail extends Model
{
    //use HasFactory;

    protected $table = 'articulo_detalles';
    public $timestamp = false;
    
    protected $fillable = [
        'id',
        'cod_fabricante',
        'created_at',
        'linea_id',
        'codarticulo',
        'origen_id',
        'marca_id'
    ];
}
