<?php

namespace App\Models\Vim;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncVimType extends Model
{
    //use HasFactory;
    
    protected $table = 'tipos';
    public $timestamp = false;
    
    protected $fillable = [
        'id',
        'code',
        'descripcion',
        'created_at'
    ];
}
