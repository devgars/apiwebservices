<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'status'
    ];

    /* 
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    
    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }


    
    //============================
    // REALCIONES 
    //============================
    
    //relacion con el pedido

    //relacion con el usuario de mmtrack
    public function userAs400() {

        return $this->hasOne('App\Models\UserAs400', 'user_as400_id', 'id');
    }

    //relacion con el usuario de mmtrack
    public function userSistem() {

        return $this->hasOne('App\Models\UserSystem', 'user_id', 'id');
    }

       
    //relacion con el tracking del pedido
    public function conductor() {

        return $this->hasOne('App\Models\MMTrack\Drivers', 'user_id', 'id');
    }


}


