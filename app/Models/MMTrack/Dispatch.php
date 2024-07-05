<?php

namespace App\Models\MMTrack;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dispatch extends Model
{
    // Define la tabla de base de datos
    protected $table = 'dispatch';

    // Protección de campos de asignación masiva
    protected $fillable = [
        'company_code',
        'branch_code',
        'order_number',
        'document_order_number',
        'customer_id',
        'customer_code',
        'social_reason',
        'number_ruc',
        'document_date',
        'order_origin',
        'tax_condition',
        'seller_code',
        'served_by',
        'warehouse_code',
        'priority_status',
        'reason_for_transfer',
        'payment_method',
        'betalings_metode',
        'payment_condition',
        'currency_code',
        'discount_customer_class',
        'discount_cond_payment',
        'document_general_status',
        'document_type',
        'document_serial_number',
        'corre_document_number',
        'imports_total',
        'discount_payment',
        'discount_amount_per_customer_class',
        'amount_taxes',
        'order_status_document',
        'print_status',
        'registration_status',
        'user_name ',
        'work',
        'date_of_work',
        'work_time',
        'created_at',
        'updated_at',
        'status',
        'delivered',
        'observaciones',
        'carrier_code_secondary',
        'referral_guide_number',
        'customer_contact_id'
    ];

    
    //===========================
    // SCOPES PARA BUSQUEDAS
    //===========================
    //obtener llamadas del dia
    public function scopeDelDia($query)
    {
        return $query->whereDate('created_at', date('Y-m-d'));
    }

    //obtener despachos entregados
    public function scopeEntregados($query)
    {
        return $query->where('delivered', 1);
    }
    //obtener despachos no entregados
    public function scopeNoEntregados($query)
    {
        return $query->where('delivered', 0);
    }


    //============================
    // REALCIONES 
    //============================

    //relacion con el tracking del pedido
    public function tracking()
    {

        return $this->hasOne('App\Models\MMTrack\DispatchTracking', 'dispatch_id', 'id');
    }

    //relacion con la calificacion del pedido
    public function calificacion()
    {

        return $this->hasOne('App\Models\MMTrack\Qualification', 'dispatch_id', 'id');
    }

    //relacion con el tracking del pedido
    public function detail()
    {

        return $this->hasOne('App\Models\MMTrack\OrderDeliveryDetail', 'dispatch', 'id');
    }



    //RELACION CON  EL CLIENTE
    public function client()
    {
        return $this->belongsTo('App\Models\Customer', 'customer_id', 'id');
    }

    //RELACION CON  los detalles del pedido
    public function details()
    {
        return $this->hasMany('App\Models\MMTrack\DispatchDetailMts', 'dispatch_id');
    }
}
