<?php

namespace App\Http\Controllers\Sync;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Controllers\Sync\Utilidades;

class BanksSync extends Controller
{
    public function ccddfcneg_facturas_consolidadas($factura)
    {
        echo '<pre>';
        print_r($factura);
    }
}
