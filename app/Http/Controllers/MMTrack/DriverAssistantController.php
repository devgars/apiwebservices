<?php

namespace App\Http\Controllers\MMTrack;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MMTrack\DriverAssistant;
use App\Models\MMTrack\OrderDelivery;
use App\Models\MMTrack\OrderDeliveryDetail;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\UserSystem;
use App\Models\GenResourceDetail;
use DB;
use Validator;

class DriverAssistantController extends Controller
{
    public function index(Request $request)
    {
            
        $ayudantes = null;
        
        if($request->mostrar_inactivos==0){
            //buscar ayudantes en la base de datos intermedia 
            //que esten activos
            $ayudantes = DriverAssistant::with('document')->where('status',1)->orderBy('id','desc')->get();
        }else{
            //buscar ayudantes en la base de datos intermedia 
            //que esten activos e inactivos
            $ayudantes = DriverAssistant::with('document')->orderBy('id','desc')->get();
        }
 
        //buscar los tipos de los documentos
        $tipos_documento = GenResourceDetail::where('resource_id',2)->get();
 
        $response = [
            "mensaje" => "Consulta Exitosa",
            "ayudantes" =>$ayudantes,
            "tipos" =>$tipos_documento,
            "mostrar_inactivos" => $request->mostrar_inactivos,
        ];

        return response()->json($response, 200);

    }

    /**
    * metodo encargado de guardar los ayudantes
    */
    public function guardar(Request $request)
    {
        
        //validando el datos necesarios
        $validacion = Validator::make($request->all(), [
            'nombre' => 'required',
            'apellido' => 'required',
            'tipo_documento' => 'required',
            'correo'     =>  'required|email',
            'numero_documento' => 'required',
        ]);

        //verificar si hay errores en la validacion
        if ($validacion->fails()) {
            return response()->json($validacion->errors(), 422);
        }

       
        DB::beginTransaction();

        try {
           
            //guardar el ayudante
            $ayudante = DriverAssistant::updateOrCreate(
                [   'id' => $request->id],
                [   'names' => $request->nombre,
                    'surnames' => $request->apellido,
                    'email' => $request->correo,
                    'document_type_id' => $request->tipo_documento,
                    'document_number' => $request->numero_documento,
                ]   
            );
        
            //validar si el ayudante posee usuario
            if(!$ayudante->user_id){

                //creamos usuario de laravel
                $usuario = User::create([
                    'name'  => $request->nombre." ".$request->apellido,
                    'email' => $request->correo,
                    'password' => Hash::make(explode(" ", $request->nombre)[0]),
                ]);
    
                //RELACIONAR CON USUARIOS DE SISTEMA MMTRACK
                $usuario_sistema = UserSystem::updateOrCreate(['user_id' => $usuario->id],
                                                                [   'company_id' => 1,
                                                                    'user_id' =>$usuario->id,
                                                                    'reg_status' => 1,
                                                                    'system_id' => 501
                                                                ]
                                                            );
            
                // Asignación del rol
                $usuario_sistema->syncRoles(["Ayudante"]);   
                
                //relacionar con el usuario creado
                DriverAssistant::where('id',$ayudante->id)->update(['user_id' => $usuario->id]);
            }

            $response = [
                "mensaje" => "Procesado con Exito",
                "ayudante" =>$ayudante,
            ];
        
            DB::commit();

            return response()->json($response, 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['mensaje' => $e->getMessage()], 502);

        }
        

    }

    /**
     * Metodo para cambiar estado del ayudante
     * Param: ID de pausa
     */
    public function cambiarEstatus($id)
    {
        $ayudante = DriverAssistant::findOrFail($id);
        $nuevoEstado = $ayudante->status == 1 ? 0 : 1;
        $ayudante->status = $nuevoEstado;
        $ayudante->save();
        
        User::where('id',$ayudante->user_id)->update(['status' => $nuevoEstado]);

       
        return response()->json(['mensaje' => 'Estado actualizado'], 200);
    }

    
    public function ayudantesActivos()
    {
   
        //buscar ayudantes en la base de datos intermedia 
        //que esten activos
        $ayudantes = DriverAssistant::select('id','names','surnames')
                                ->where('status',1)
                                ->orderBy('names','Asc')
                                ->get();
 
        return response()->json($ayudantes, 200);


    }


    /**
     * Metodo para TRASBORDAR AYUDANTE
     * Param: ID de pausa
    */
    public function trasbordoAyudante($id,$usuario_id, Request $request)
    {

        $order = OrderDelivery::findOrFail($id);
        $order->assistant_id = $usuario_id;
        $order->save();

        OrderDeliveryDetail::whereIn('id',$request->ordenes)->update(['assistant' => 1, 'assistant_date'=>date('Y-m-d H:i:s')]);
        
 
        return response()->json(['mensaje' => 'Actualizado'], 200);
    }

    public function posponerEntrega(Request $request)
    {

        OrderDeliveryDetail::whereIn('id',$request->ordenes)->update(['assistant_date'=>date('Y-m-d H:i:s')]);
        
 
        return response()->json(['mensaje' => 'Actualizado'], 200);
    }
    
    
}


