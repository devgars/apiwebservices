<?php

namespace App\Models\Vim;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncVimBrand extends Model
{
    //use HasFactory;

    protected $table = 'marcas';
    public $timestamp = false;
    
    protected $fillable = [
        'id',
        'code',
        // 'descripcion',
        'descripcon',
        'created_at',
        'user_id'
    ];
}
