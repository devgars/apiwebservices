<?php

namespace App\Models\Vim;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncVimOrigin extends Model
{
    // use HasFactory;

    protected $table = 'origenes';
    public $timestamp = false;
    
    protected $fillable = [
        'id',
        'code',
        'descripcion',
        'created_at',
        'user_id'
    ];
}
