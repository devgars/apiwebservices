<?php

namespace App\Http\Controllers\MMTrack;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MMTrack\Drivers;
use DB;
use Validator;


class ConductoresController extends Controller
{
    public function index(Request $request)
    {
            
        $conductores = null;
        
        if($request->mostrar_inactivos==0){
            //buscar conductores en la base de datos intermedia 
            //que esten activos
            $conductores = Drivers::with('user')->where('status',1)->orderBy('id','desc')->get();
        }else{
            //buscar conductores en la base de datos intermedia 
            //que esten activos e inactivos
            $conductores = Drivers::with('user')->orderBy('id','desc')->get();
        }

        //buscar los tipos documentos de los conductores
        $tipos_documento = [['id' => 1, 'name' =>'DNI']];
 

        $response = [
            "mensaje" => "Consulta Exitosa",
            "conductores" =>$conductores,
            "tipos_documento" =>$tipos_documento,
            "mostrar_inactivos" => $request->mostrar_inactivos
        ];


        return response()->json($response, 200);


    }

    /**
     * metodo encargado de guardar los conductores
     */
    public function guardar(Request $request)
    {
        
        //validando el datos necesarios
        $validacion = Validator::make($request->all(), [
            'nombres' => 'required',
            'apellidos' => 'required',
            'tipo_documento' => 'required',
            'numero_documento' => 'required'
        ]);

        //verificar si hay errores en la validacion
        if ($validacion->fails()) {
            return response()->json($validacion->errors(), 422);
        }
 
        //guardar el conductor
        $conductor = Drivers::create([
            'names' => $request->nombres,
            'surnames' => $request->apellidos,
            'document_type_id' => $request->tipo_documento,
            'document_number' => $request->numero_documento,
            'status' => 1,
            
        ]);
        
        $response = [
            "mensaje" => "Registro Creado con Exito",
            "conductor" =>$conductor,
        ];

        
       

        return response()->json($response, 200);


    }

    /**
     * Metodo para cambiar estado del vehiculo
     * Param: ID de pausa
     */
    public function cambiasEstatus($id)
    {
        $conductor = Drivers::findOrFail($id);
        $nuevoEstado = $conductor->status == 1 ? 0 : 1;
        $conductor->status = $nuevoEstado;
        $conductor->save();
        return response()->json(['mensaje' => 'Estado actualizado'], 200);
    }

 
}
