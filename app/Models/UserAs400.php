<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAs400 extends Model
{
    // Define la tabla de base de datos
    protected $table = 'user_users';

    // Protección de campos de asignación masiva
    protected $fillable = [
        'user_id',
        'updated_at',
        'startdate',
        'second_name',
        'reg_status',
        'mother_last_name',
        'last_name',
        'first_name',
        'email',
        'created_at',
        'country_id',
        'code',
        'cellphone',
        'birthdate',
    ];
    protected $appends = ['nombre_completo'];

    public function getNombreCompletoAttribute() 
    { 
        return $this->first_name. ' '. $this->second_name.' '.$this->last_name .' '.$this->mother_last_name;
    }


       
    //==========================
    //RELACIONES
    //==========================
    //relacion con el usuario de mmtrack
     public function user()
    {
        return $this->belongsTo('App\Models\User');
    }
}
