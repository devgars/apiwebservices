<?php

namespace App\Models\Customers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerGroup extends Model
{
    protected $table = 'customer_groups';

    protected $fillable = [
        'customer_id',
        'customer_group_id',
        'reg_status',
        'created_at',
        'updated_at'
    ];
}
