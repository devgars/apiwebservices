<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;

class UserSystem extends Model
{
    use HasFactory, HasRoles;

        // Define la tabla de base de datos
        protected $table = 'user_systems';
       
        protected $guard_name = 'api';

        // Protección de campos de asignación masiva
        protected $fillable = [
            'company_id',
            'user_id',
            'reg_status',
            'system_id',
            'updated_at',
            'created_at',
        ];

    //relacion con el usuario de mmtrack
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }
}
