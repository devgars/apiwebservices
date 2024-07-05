<?php

namespace App\Http\Controllers;

use App\Models\ModelClientJT;
use Illuminate\Http\Request;

use DB;
// use Maatwebsite\Excel\Concerns\ToArray;
use stdClass;

class ClientJTController extends Controller
{
    public function ObtenerDatosCliente()
    {
        return response()->json(ModelClientJT::all(),200);
    }

    // public function ObtenerDatosCliente()
    // {
    //     $rs = DB::connection('pgsql')->table('clientes')->get()->toArray();

    //     if (is_array($rs) && count($rs) > 0) {
    //         $arrayResult = array();
    //         foreach ($rs as  $value) {
    //             $value->id = trim($value->id);
    //             $value->nrodocidentida = trim($value->nrodocidentida);
                                
    //             array_push($arrayResult, $value);
    //         }
    //     }
    //     $object = new stdClass();
    //     $object->success = true;
    //     $object->msg = "Products data";
    //     $object->items = $arrayResult;
    //     //dd($object);
        
    //     return response()->json($object, 200, array('Content-Type' => 'application/json; charset=utf-8'));
    // }

    public function insertarCliente(Request $request)
    {
        $cliente = ModelClientJT::create($request->all());
        return response($cliente,200);
    }
}
