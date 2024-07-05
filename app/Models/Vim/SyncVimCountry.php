<?php

namespace App\Models\Vim;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncVimCountry extends Model
{
    //use HasFactory;

    protected $table = 'pais';
    public $timestamp = false;
    
    protected $fillable = [
        'id',
        'name',
        'iso_code',
        'user_id',
        'created_at',
        'code'
    ];
}
