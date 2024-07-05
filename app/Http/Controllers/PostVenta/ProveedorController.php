<?php

namespace App\Http\Controllers\PostVenta;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
class ProveedorController extends Controller
{
    public function index(Request $request){
        $data=DB::table('poventa_proveedor_request as t1')
        ->select(
            "t1.id",
            "t1.id_product_detail_request",
            "t1.code",
            "t1.brand",
            "t1.description",
            "t1.linea",
            "t1.origin",
            't1.unidad'
        )->where('id_request', $request->id_request)
        ->orderBy('id', 'asc')
        ->get();
        return response()->json($data, 200);
    }
}
