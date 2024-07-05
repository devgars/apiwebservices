<?php

namespace App\Http\Controllers\MMTrack;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GenResourceDetail;
use App\Models\Ubigeo;
use App\Models\MMTrack\AgencyBranches;
use Validator;

class AgenciaController extends Controller
{
    public function index(Request $request)
    {
        //obtener agencias
        $agencias = GenResourceDetail::activos()
                                        ->where('resource_id',23)
                                        ->where(function ($query) use ($request) {
                                            $query->where('code',  '%' .$request->busqueda. '%');
                                            $query->orWhere('name', 'ILIKE', '%' . $request->busqueda . '%');
                                        })
                                        ->with('Sucursales.Departementos')
                                        ->with(['Sucursales' => function($query){
                                            $query->where('status', 1);
                                        }])
                                        ->withCount(['Sucursales' => function($query){
                                            $query->where('status', 1);
                                        }])
                                        ->paginate(10);
        //obtener los departamentos
        $departamentos =Ubigeo::departamentos()->activos()->orderBy('id','desc')->get();
        
        $response = [
            "mensaje" => "Consulta Exitosa",
            "agencias" =>$agencias,
            "departamentos" =>$departamentos,
       
        ];


        return response()->json($response, 200);

    }

    public function obtenerDepartamentos()
    {
        //obtener los departamentos
        $departamentos =Ubigeo::departamentos()->activos()->orderBy('id','desc')->paginate(10);

        return response()->json($departamentos, 200);
    }

    /**
     * metodo encargado de guardar la sucursales de las agencias
     */
    public function guardarSucursal(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'sucursal_name'  =>  'required|string|max:50',
            'departamentos'  =>  'required',
            'agencia_id'  =>  'required',
        ]);

        if($validation->fails()){
            return response()->json($validation->errors(), 422);
        }

        //DB::transaction(function () use($request) {

            //buscar carrier code 
            $agencia = GenResourceDetail::where('id',$request->agencia_id)->first();
            //creamos la sucursal
            $sucuarsal = AgencyBranches::create([
                'agencia_id'  => $request->agencia_id,
                'carrier_code' => $agencia ? $agencia->code : 1,
                'name' => $request->sucursal_name,
                'status' => 1,
            ]);
           
            //relacionar con los departamentos
            $sucuarsal->Departementos()->sync($request->departamentos);  
            
              

            return response()->json(['mensaje' => 'Sucursal guardada con éxito'], 200);
         
        //});


    }

    /**
     * Metodo para cambiar estado del vehiculo
     * Param: ID de pausa
    */
    public function eliminar($id)
    {
        $sucursal = AgencyBranches::findOrFail($id);
        $nuevoEstado = 0;
        $sucursal->status = $nuevoEstado;
        $sucursal->save();
        return response()->json(['mensaje' => 'Registro Eliminado'], 200);
    }

    
}
