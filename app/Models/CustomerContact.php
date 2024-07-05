<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerContact extends Model
{
    // Define la tabla de base de datos
    protected $table = 'customer_contacts';

    // Protección de campos de asignación masiva
    protected $fillable = [
        'id',
        'customer_id',
        'customer_contact_number',
        'work_position_id',
        'contact_name',
        'contact_phone',
        'contact_email',
        'created_at',
        'updated_at',
        'reg_status',
        'identification_type_id',
        'identification_number',
        'contact_address',
    ];
}
