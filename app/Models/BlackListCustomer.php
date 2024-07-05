<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlackListCustomer extends Model
{
    protected $table = 'black_list_customers';
    protected $fillable = [
        'id',
        'customer_id'
    ];
}
