<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    // Define la tabla de base de datos
    protected $table = 'customers';

    // Protección de campos de asignación masiva
    protected $fillable = [
        'id',
        'code',
        'company_type_id',
        'document_type_id',
        'document_number',
        'name_social_reason',
        'tradename',
        'ruc_code_old',
        'economic_group_id',
        'business_turn',
        'country_id',
        'region_id',
        'client_class',
        'reg_date',
        'capital_amount',
        'tax_condition',
        'currency_code',
        'max_credit_limit',
        'consumption_amount',
        'sales_block',
        'credit_block',
        'created_at',
        'updated_at',
        'reg_status',
    ];


   
    //relacion con el tracking del pedido
    public function orderDetail() {

        return $this->hasOne('App\Models\MMTrack\OrderDeliveryDetail', 'customer_id', 'id');
    }
 
    //relacion con  los pedido
    public function dispatchs() {

        return $this->hasMany('App\Models\MMTrack\Dispatch', 'customer_id', 'id');
    }

    //relacion con las direcciones del cliente
    public function addresses() {

        return $this->hasMany('App\Models\CustomerAddress', 'customer_id', 'id');
    }

    //relacion con el tracking del pedido
    public function company() {

        return $this->hasOne('App\Models\GenResourceDetail', 'id', 'company_type_id');
    }

    //relacion con el tracking del pedido
    public function document() {

        return $this->hasOne('App\Models\GenResourceDetail', 'id', 'document_type_id');
    }

    
    //relacion con las direcciones del cliente
    public function direcciones() {

        return $this->hasMany('App\Models\Vistas\Direcciones', 'customer_id', 'id');
    }
    
}

