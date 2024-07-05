<?php

namespace App\Http\Controllers\MMTrack;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MMTrack\Transit;
use Validator;

class ConfiguracionesController extends Controller
{
    public function index(Request $request)
    {
            
        $configuraciones = null;
        
        if($request->mostrar_inactivos==0){
            //buscar configuraciones en la base de datos intermedia 
            //que esten activos
            $configuraciones = Transit::where('status',1)->orderBy('id','desc')->get();
        }else{
            //buscar configuraciones en la base de datos intermedia 
            //que esten activos e inactivos
            $configuraciones = Transit::orderBy('id','desc')->get();
        }
 

        $response = [
            "mensaje" => "Consulta Exitosa",
            "configuraciones" =>$configuraciones,
            "mostrar_inactivos" => $request->mostrar_inactivos
        ];


        return response()->json($response, 200);


    }

    /**
    * metodo encargado de guardar los vehiculos
    */
    public function guardar(Request $request)
    {
        
        //validando el datos necesarios
        $validacion = Validator::make($request->all(), [
            'descripcion_as' => 'required',
            'descripcion_web' => 'required',
            'codigo_as' => 'required',
            'mensaje' => 'required',
            
        ]);

        //verificar si hay errores en la validacion
        if ($validacion->fails()) {
            return response()->json($validacion->errors(), 422);
        }
        
        //guardar el transito
        $transito = Transit::updateOrCreate(
            [   'id' => $request->codigo_as],

            [   'description_as' => $request->descripcion_as,
                'description_web' => $request->descripcion_web,
                
            ]   
        );

        Transit::where('description_web',$request->descripcion_web)->update(['mensaje' => $request->mensaje]); 

        
        $response = [
            "mensaje" => "Transito con Exito",
            "transito" =>$transito,
        ];

        
       

        return response()->json($response, 200);


    }

    
    /**
     * Metodo para cambiar estado del transito
     * Param: ID de pausa
     */
    public function cambiasEstatus($id)
    {
        $Transit = Transit::findOrFail($id);
        $nuevoEstado = $Transit->status == 1 ? 0 : 1;
        $Transit->status = $nuevoEstado;
        $Transit->save();
        return response()->json(['mensaje' => 'Transito actualizado'], 200);
    }

    public function obtenerMensajes(Request $request)
    {
        $mensajes = Transit::select('description_web','mensaje')->distinct()->where('status',1)->get();
        response()->json($mensajes, 200);
        return response()->json($mensajes, 200);
    }
}
