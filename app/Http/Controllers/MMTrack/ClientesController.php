<?php

namespace App\Http\Controllers\MMTrack;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use DB;


class ClientesController extends Controller
{
    public function index(Request $request)
    {
            
        $clientes = null;
        $query_direcciones = "select direccion_completa FROM v_direcciones where customer_code = '020099'";
       
        if($request->mostrar_inactivos==0){
            //buscar clientes en la base de datos intermedia 
            //que esten activos
            $clientes = Customer::with('addresses','company','document','direcciones')
                                    ->where('reg_status',1)
                                    ->where('name_social_reason','ILIKE','%'.$request->busqueda.'%')
                                    ->orWhere('document_number', 'ILIKE', '%'.$request->busqueda.'%') 
                                    ->orWhere('code', 'ILIKE', '%'.$request->busqueda.'%') 
                                    ->orderBy('id','desc')
                                    ->paginate(10);
        }else{
            //buscar clientes en la base de datos intermedia 
            //que esten activos e inactivos
            $clientes = Customer::with('addresses','company','document','direcciones')
                                    ->select(DB::raw($query_direcciones))
                                    ->orderBy('id','desc')
                                    ->where('name_social_reason','ILIKE','%'.$request->busqueda.'%')
                                    ->orWhere('document_number', 'ILIKE', '%'.$request->busqueda.'%') 
                                    ->orWhere('code', 'ILIKE', '%'.$request->busqueda.'%')
                                    ->paginate(10);
        }
 

        $response = [
            "mensaje" => "Consulta Exitosa",
            "clientes" =>$clientes,
            "mostrar_inactivos" => $request->mostrar_inactivos
        ];


        return response()->json($response, 200);


    }

    public function clientesSelect(Request $request)
    {
            
        $clientes = null;
        $query_direcciones = "select direccion_completa FROM v_direcciones where customer_code = '020099'";
       
      
        //que esten activos
        $clientes = Customer::select('id','name_social_reason','document_number','code')
                            ->where('reg_status',1)
                            ->where('name_social_reason','ILIKE','%'.$request->busqueda.'%')
                            ->orWhere('document_number', 'ILIKE', '%'.$request->busqueda.'%') 
                            ->orWhere('code', 'ILIKE', '%'.$request->busqueda.'%') 
                            ->orderBy('id','desc')
                            ->paginate(10);
 

        $json = [
                "results"=> [
                    [
                        "value"=> 1,
                        "label"=> 'Audi',
                    ],
                    [
                        "value"=> 2,
                        "label"=> 'Mercedes',
                    ],
                    [
                        "value"=> 3,
                        "label"=> 'BMW',
                    ],
                ],
                "has_more" => true,
        ];





        return response()->json($json , 200);


    }

    
}

