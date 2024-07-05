<?php

namespace App\Models\PostVenta;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomersContact extends Model
{
    use HasFactory;
    protected $table = 'customer_contacts';
    protected $hidden = [
        'updated_at'
    ];
}
